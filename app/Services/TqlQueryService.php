<?php

namespace ProjectsManager\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use ProjectsManager\CustomField;
use ProjectsManager\Project;
use ProjectsManager\Sprint;
use ProjectsManager\Task;
use ProjectsManager\User;

class TqlQueryService
{
    /**
     * Parse and execute a TQL query string into an Eloquent query builder.
     *
     * @param string $tql
     * @param int|null $defaultProjectId
     * @return array{builder: Builder|null, error: string|null, clauses: array, order_by: array}
     */
    public function parse(string $tql, ?int $defaultProjectId = null): array
    {
        $raw = trim($tql);
        if ($raw === '') {
            $builder = Task::query();
            if ($defaultProjectId) {
                $builder->where('project_id', $defaultProjectId);
            }
            return [
                'builder' => $builder->orderByDesc('id'),
                'error' => null,
                'clauses' => [],
                'order_by' => ['id' => 'desc'],
            ];
        }

        // Separate ORDER BY if present
        $orderBy = [];
        $whereString = $raw;
        if (preg_match('/^(.*?)\s+ORDER\s+BY\s+(.*)$/i', $raw, $matches)) {
            $whereString = trim($matches[1]);
            $orderString = trim($matches[2]);
            $orderBy = $this->parseOrderBy($orderString);
        }

        $clauses = [];
        $error = null;

        if ($whereString !== '') {
            $clauseTokens = $this->splitClauses($whereString);
            foreach ($clauseTokens as $token) {
                $parsed = $this->parseSingleClause($token);
                if (isset($parsed['error'])) {
                    return [
                        'builder' => null,
                        'error' => $parsed['error'],
                        'clauses' => [],
                        'order_by' => [],
                    ];
                }
                $clauses[] = $parsed;
            }
        }

        // Build Eloquent Query
        $builder = Task::query()->with(['project', 'assignee', 'reporter', 'sprintRelation']);

        if ($defaultProjectId && ! $this->hasProjectClause($clauses)) {
            $builder->where('tasks.project_id', $defaultProjectId);
        }

        foreach ($clauses as $clause) {
            $this->applyClauseToBuilder($builder, $clause);
        }

        // Apply Order By
        if (! empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                if ($field === 'priority') {
                    $builder->orderByRaw("CASE tasks.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END " . ($direction === 'asc' ? 'DESC' : 'ASC'));
                } elseif (in_array($field, ['id', 'name', 'status', 'created_at', 'updated_at', 'due_date', 'story_points', 'issue_key'])) {
                    $builder->orderBy("tasks.{$field}", $direction);
                }
            }
        } else {
            $builder->orderByDesc('tasks.id');
        }

        return [
            'builder' => $builder,
            'error' => null,
            'clauses' => $clauses,
            'order_by' => $orderBy,
        ];
    }

    private function hasProjectClause(array $clauses): bool
    {
        foreach ($clauses as $c) {
            if (in_array(strtolower($c['field']), ['project', 'project_id', 'proj'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Split string by top-level AND / OR.
     */
    private function splitClauses(string $str): array
    {
        // Simple regex splitting by AND (case-insensitive) while respecting quotes
        // We support AND chaining primarily
        $pattern = '/\s+AND\s+/i';
        $parts = preg_split($pattern, $str);
        return array_map('trim', $parts);
    }

    /**
     * Parse a single atomic clause: field operator value
     */
    private function parseSingleClause(string $clauseStr): array
    {
        // Match operators: !=, <=, >=, =, <, >, IN, NOT IN, ~, IS NOT, IS
        $pattern = '/^([a-zA-Z0-9_\[\]]+)\s*(!=|<=|>=|=|<|>|~|\bNOT\s+IN\b|\bIN\b|\bIS\s+NOT\b|\bIS\b)\s*(.*)$/i';
        if (! preg_match($pattern, trim($clauseStr), $m)) {
            return ['error' => "Cú pháp không hợp lệ gần: '{$clauseStr}'. Định dạng chuẩn: field = \"value\""];
        }

        $field = trim($m[1]);
        $operator = strtoupper(trim($m[2]));
        $rawValue = trim($m[3]);

        $value = $this->parseValue($rawValue, $operator);

        return [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'raw_value' => $rawValue,
        ];
    }

    private function parseValue(string $val, string $op): mixed
    {
        $val = trim($val);

        // IN / NOT IN list: ("a", "b", 123)
        if (in_array($op, ['IN', 'NOT IN'])) {
            if (str_starts_with($val, '(') && str_ends_with($val, ')')) {
                $inner = substr($val, 1, -1);
                // check if it's a function call like openSprints()
                if (strtolower(trim($inner)) === 'opensprints()') {
                    return $this->resolveFunction('opensprints()');
                }
                $items = explode(',', $inner);
                return array_map(function ($item) {
                    $item = trim($item);
                    return trim($item, "\"'");
                }, $items);
            }
            if (strtolower($val) === 'opensprints()') {
                return $this->resolveFunction('opensprints()');
            }
        }

        // Functions
        if (str_ends_with($val, '()')) {
            return $this->resolveFunction(strtolower($val));
        }

        // Strip quotes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            return substr($val, 1, -1);
        }

        // Keywords
        if (in_array(strtolower($val), ['null', 'empty'])) {
            return null;
        }

        return $val;
    }

    private function resolveFunction(string $fn): mixed
    {
        return match (strtolower($fn)) {
            'currentuser()' => Auth::id() ?? 0,
            'opensprints()' => Sprint::where('status', 'active')->pluck('id')->all(),
            'today()' => Carbon::today()->toDateString(),
            'now()' => Carbon::now()->toDateTimeString(),
            default => $fn,
        };
    }

    private function parseOrderBy(string $orderStr): array
    {
        $res = [];
        $parts = explode(',', $orderStr);
        foreach ($parts as $p) {
            $p = trim($p);
            if (preg_match('/^([a-zA-Z0-9_]+)(\s+(ASC|DESC))?$/i', $p, $m)) {
                $col = strtolower($m[1]);
                $dir = isset($m[3]) ? strtolower($m[3]) : 'asc';
                $res[$col] = $dir;
            }
        }
        return $res;
    }

    private function applyClauseToBuilder(Builder $builder, array $clause): void
    {
        $field = strtolower($clause['field']);
        $op = $clause['operator'];
        $val = $clause['value'];

        // Check if custom field: cf[environment] or cf[severity]
        if (preg_match('/^cf\[([a-zA-Z0-9_\-]+)\]$/', $field, $cfMatch)) {
            $cfKey = $cfMatch[1];
            $this->applyCustomFieldClause($builder, $cfKey, $op, $val);
            return;
        }

        // Standard Fields
        switch ($field) {
            case 'project':
            case 'project_id':
            case 'proj':
                if (is_numeric($val)) {
                    $builder->where('tasks.project_id', $op === '!=' ? '!=' : '=', (int) $val);
                } elseif (is_array($val)) {
                    $builder->whereIn('tasks.project_id', $val);
                } else {
                    $builder->whereHas('project', function ($q) use ($op, $val) {
                        $q->where('name', $op === '~' ? 'LIKE' : '=', $op === '~' ? "%{$val}%" : $val)
                          ->orWhere('project_code', '=', $val);
                    });
                }
                break;

            case 'status':
                $this->applyStandardOrList($builder, 'tasks.status', $op, $val);
                break;

            case 'priority':
                $this->applyStandardOrList($builder, 'tasks.priority', $op, $val);
                break;

            case 'issue_type':
            case 'type':
                $this->applyStandardOrList($builder, 'tasks.issue_type', $op, $val);
                break;

            case 'issue_key':
            case 'key':
                $this->applyStandardOrList($builder, 'tasks.issue_key', $op, $val);
                break;

            case 'assignee':
            case 'assignee_id':
                if ($val === null || $val === 'unassigned') {
                    $op === '!=' || $op === 'IS NOT'
                        ? $builder->whereNotNull('tasks.assignee_id')
                        : $builder->whereNull('tasks.assignee_id');
                } elseif (is_numeric($val)) {
                    $builder->where('tasks.assignee_id', $op === '!=' ? '!=' : '=', (int) $val);
                } elseif (is_array($val)) {
                    $builder->whereIn('tasks.assignee_id', $val);
                } else {
                    // Search by user name or email
                    $matchedIds = User::where('name', 'like', "%{$val}%")
                        ->orWhere('email', 'like', "%{$val}%")
                        ->pluck('id')
                        ->all();
                    $builder->whereIn('tasks.assignee_id', $matchedIds);
                }
                break;

            case 'reporter':
            case 'reporter_id':
                if ($val === null) {
                    $op === '!=' || $op === 'IS NOT'
                        ? $builder->whereNotNull('tasks.reporter_id')
                        : $builder->whereNull('tasks.reporter_id');
                } elseif (is_numeric($val)) {
                    $builder->where('tasks.reporter_id', $op === '!=' ? '!=' : '=', (int) $val);
                } elseif (is_array($val)) {
                    $builder->whereIn('tasks.reporter_id', $val);
                } else {
                    $matchedIds = User::where('name', 'like', "%{$val}%")
                        ->orWhere('email', 'like', "%{$val}%")
                        ->pluck('id')
                        ->all();
                    $builder->whereIn('tasks.reporter_id', $matchedIds);
                }
                break;

            case 'sprint':
            case 'sprint_id':
                if ($val === null || $val === 'none') {
                    $op === '!=' || $op === 'IS NOT'
                        ? $builder->whereNotNull('tasks.sprint_id')
                        : $builder->whereNull('tasks.sprint_id');
                } elseif (is_array($val)) {
                    $op === 'NOT IN'
                        ? $builder->whereNotIn('tasks.sprint_id', $val)
                        : $builder->whereIn('tasks.sprint_id', $val);
                } elseif (is_numeric($val)) {
                    $builder->where('tasks.sprint_id', $op === '!=' ? '!=' : '=', (int) $val);
                } else {
                    $sprintIds = Sprint::where('name', 'like', "%{$val}%")->pluck('id')->all();
                    $builder->whereIn('tasks.sprint_id', $sprintIds);
                }
                break;

            case 'story_points':
            case 'points':
                $builder->where('tasks.story_points', $op, (float) $val);
                break;

            case 'due_date':
            case 'due':
                $builder->whereDate('tasks.due_date', $op, $val);
                break;

            case 'summary':
            case 'title':
            case 'name':
                if ($op === '~') {
                    $builder->where('tasks.name', 'LIKE', "%{$val}%");
                } else {
                    $builder->where('tasks.name', $op, $val);
                }
                break;

            case 'text':
            case 'description':
                $builder->where(function ($q) use ($val) {
                    $q->where('tasks.name', 'LIKE', "%{$val}%")
                      ->orWhere('tasks.description', 'LIKE', "%{$val}%");
                });
                break;

            default:
                // Fallback check if it matches a custom field key directly without cf[]
                if (CustomField::where('field_key', $field)->exists()) {
                    $this->applyCustomFieldClause($builder, $field, $op, $val);
                }
                break;
        }
    }

    private function applyStandardOrList(Builder $builder, string $column, string $op, mixed $val): void
    {
        if (is_array($val)) {
            if ($op === 'NOT IN' || $op === '!=') {
                $builder->whereNotIn($column, $val);
            } else {
                $builder->whereIn($column, $val);
            }
        } elseif ($val === null) {
            if ($op === '!=' || $op === 'IS NOT') {
                $builder->whereNotNull($column);
            } else {
                $builder->whereNull($column);
            }
        } else {
            if ($op === '~') {
                $builder->where($column, 'LIKE', "%{$val}%");
            } else {
                $builder->where($column, $op, $val);
            }
        }
    }

    private function applyCustomFieldClause(Builder $builder, string $cfKey, string $op, mixed $val): void
    {
        $customField = CustomField::where('field_key', $cfKey)->first();
        if (! $customField) {
            return;
        }

        $builder->whereHas('customFieldValues', function ($q) use ($customField, $op, $val) {
            $q->where('custom_field_id', $customField->id);
            if (is_array($val)) {
                $op === 'NOT IN'
                    ? $q->whereNotIn('value', $val)
                    : $q->whereIn('value', $val);
            } elseif ($op === '~') {
                $q->where('value', 'LIKE', "%{$val}%");
            } else {
                $q->where('value', $op, (string) $val);
            }
        });
    }
}

