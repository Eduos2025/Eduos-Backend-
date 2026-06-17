@extends('layouts.master')

@section('page_title', 'School Subscriptions')

@section('content')
<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title">Manage School Subscriptions</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-highlight">
            <li class="nav-item"><a href="#manage-subs" class="nav-link active" data-toggle="tab">All Subscriptions</a></li>
            <li class="nav-item"><a href="#audit-logs" class="nav-link" data-toggle="tab">Audit Logs</a></li>
        </ul>

        <div class="tab-content">
            <!-- Subscriptions Tab -->
            <div class="tab-pane fade show active" id="manage-subs">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>School Name</th>
                            <th>Subdomain</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Expires On</th>
                            <th class="text-center">Manage Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tenants as $t)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $t->name }}</td>
                            <td><a href="http://{{ $t->domain->domain ?? '' }}" target="_blank">{{ $t->domain->domain ?? '' }}</a></td>
                            <td>{{ $t->plan ? $t->plan->name : 'None' }}</td>
                            <td>
                                <span class="badge {{ in_array($t->subscription_status, ['active', 'trialing']) ? 'badge-success' : 'badge-danger' }}">
                                    {{ strtoupper($t->subscription_status) }}
                                </span>
                            </td>
                            <td>{{ $t->expires_at ? \Carbon\Carbon::parse($t->expires_at)->format('d M, Y') : 'N/A' }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    @if ($t->subscription_status !== 'suspended')
                                        <form action="{{ route('saas.admin.suspend', Qs::hash($t->id)) }}" method="POST" style="display:inline-block; margin-right: 5px;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-xs">Suspend</button>
                                        </form>
                                    @else
                                        <form action="{{ route('saas.admin.activate', Qs::hash($t->id)) }}" method="POST" style="display:inline-block; margin-right: 5px;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-xs">Activate</button>
                                        </form>
                                    @endif

                                    <!-- Quick actions for extend and upgrade -->
                                    <button class="btn btn-info btn-xs mr-1" onclick="openExtendModal('{{ Qs::hash($t->id) }}', '{{ $t->name }}')">Extend</button>
                                    <button class="btn btn-warning btn-xs" onclick="openUpgradeModal('{{ Qs::hash($t->id) }}', '{{ $t->name }}', '{{ $t->plan_id }}')">Plan</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Audit Logs Tab -->
            <div class="tab-pane fade" id="audit-logs">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>School ID</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $l)
                        <tr>
                            <td>{{ $l->created_at }}</td>
                            <td>{{ $l->tenant_id ?: 'Central' }}</td>
                            <td><span class="badge badge-secondary">{{ strtoupper($l->action) }}</span></td>
                            <td>{{ $l->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Extend Subscription Modal -->
<div id="extendModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary text-center">
                <h5 class="modal-title text-white">Extend Subscription</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="extendForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted">School: <strong id="extend-school-name" class="text-white"></strong></p>
                    <div class="form-group">
                        <label class="text-white">Days to Add</label>
                        <input type="number" class="form-control" name="days" value="14" required min="1">
                    </div>
                    <button type="submit" class="btn btn-success btn-block mt-3">Apply Extension</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upgrade Plan Modal -->
<div id="upgradeModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary text-center">
                <h5 class="modal-title text-white">Change Subscription Plan</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="upgradeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted">School: <strong id="upgrade-school-name" class="text-white"></strong></p>
                    <div class="form-group">
                        <label class="text-white">Choose New Plan</label>
                        <select class="form-control" name="plan_id" id="upgrade-plan-select">
                            @foreach ($plans as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-block mt-3">Update Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openExtendModal(hash, name) {
        var action = "{{ route('saas.admin.extend', ':id') }}".replace(':id', hash);
        $('#extendForm').attr('action', action);
        $('#extend-school-name').text(name);
        $('#extendModal').modal('show');
    }

    function openUpgradeModal(hash, name, planId) {
        var action = "{{ route('saas.admin.upgrade', ':id') }}".replace(':id', hash);
        $('#upgradeForm').attr('action', action);
        $('#upgrade-school-name').text(name);
        $('#upgrade-plan-select').val(planId);
        $('#upgradeModal').modal('show');
    }
</script>
@endsection
