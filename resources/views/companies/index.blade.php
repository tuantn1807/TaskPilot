@extends('layouts.app')
@section('content')
<section class="tp-page-head">
    <div><span class="tp-eyebrow" style="font-size:13px; font-weight:800; letter-spacing:0.1em; color:#3b82f6;">CUSTOMERS</span><h1 style="font-size:30px; font-weight:750; margin-top:4px;">Khách hàng</h1><p style="font-size:15px; color:#64748b; margin-top:6px;">Quản lý thông tin liên hệ và các dự án theo từng khách hàng.</p></div>
    <a href="{{ route('companies.create') }}" class="btn btn-primary" style="font-size:14px; font-weight:600; padding:8px 16px;"><i class="fa fa-plus"></i> Thêm khách hàng</a>
</section>

<section class="tp-surface">
    <div class="tp-surface-header" style="padding:16px 20px;"><strong style="font-size:15px;">{{ $companies->count() }} khách hàng</strong></div>
    <div class="table-responsive">
        <table class="table tp-table">
            <thead>
                <tr>
                    <th style="font-size:13px; padding:12px 16px;">Khách hàng</th>
                    <th style="font-size:13px; padding:12px 16px;">Liên hệ</th>
                    <th style="font-size:13px; padding:12px 16px;">Dự án</th>
                    <th style="padding:12px 16px;"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($companies as $company)
                <tr onclick="if(!event.target.closest('a, button, form, input')) window.location.href='{{ route('companies.show', $company) }}';" style="cursor: pointer;">
                    <td style="padding:14px 16px;">
                        <a href="{{ route('companies.show', $company) }}" style="font-size:15.5px; font-weight:700; color:#2563eb;">{{ $company->name }}</a>
                        <span class="tp-meta" style="font-size:13.5px; color:#64748b; display:block; margin-top:4px;">{{ \Illuminate\Support\Str::limit($company->description, 80) }}</span>
                    </td>
                    <td style="padding:14px 16px; font-size:14px;">
                        <div>{{ $company->contact_email ?: 'Chưa có email' }}</div>
                        <span class="tp-meta" style="font-size:13px; color:#64748b; display:block; margin-top:3px;">{{ $company->contact_phone ?: 'Chưa có số điện thoại' }}</span>
                    </td>
                    <td style="padding:14px 16px;">
                        <span class="tp-badge" style="font-size:12px; font-weight:700; padding:5px 11px; border-radius:99px; background:#eff6ff; color:#2563eb;">{{ $company->projects_count }} dự án</span>
                    </td>
                    <td class="text-right" style="white-space:nowrap; padding:14px 16px;">
                        <a class="btn btn-default btn-sm" href="{{ route('companies.edit', $company) }}" style="font-size:13px;">Chỉnh sửa</a>
                        @if($company->projects_count === 0)
                            <form method="POST" action="{{ route('companies.destroy', $company) }}" style="display:inline-block;margin-left:6px;" onsubmit="return confirm('Xóa khách hàng {{ addslashes($company->name) }}? Thao tác này không thể hoàn tác.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-default btn-sm text-danger" style="font-size:13px;"><i class="fa fa-trash"></i> Xóa</button>
                            </form>
                        @else
                            <span class="text-muted" style="margin-left:8px;font-size:13px;" title="Không thể xóa khách hàng đang có dự án">Đang có dự án</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="tp-empty" style="font-size:15px;"><i class="fa fa-building-o" style="font-size:32px;"></i>Chưa có khách hàng. Thêm khách hàng đầu tiên để gắn với dự án.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
