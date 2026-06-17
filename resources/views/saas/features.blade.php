@extends('layouts.saas_master')

@section('page_title', 'Platform Features')

@section('content')
<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="text-white font-weight-bold">Powerful Features Built for Modern Education</h1>
        <p class="lead text-muted">A modular platform built to simplify, automate, and secure your school administrative tasks.</p>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-dark border-secondary p-3 h-100">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-2x mb-3">badge</span>
                    <h5 class="text-white font-weight-bold">Student Management</h5>
                    <p class="text-muted">Onboard students, record parent associations, manage promotions, track class records, and issue dynamic student ID cards.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-dark border-secondary p-3 h-100">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-2x mb-3">edit_note</span>
                    <h5 class="text-white font-weight-bold">Academic Marks & Grading</h5>
                    <p class="text-muted">Manage exam periods, record grading definitions, perform bulk uploads, and print elegant tabulation sheets and student report cards.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-dark border-secondary p-3 h-100">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-2x mb-3">calendar_month</span>
                    <h5 class="text-white font-weight-bold">Timetables & Slots</h5>
                    <p class="text-muted">Generate school weekly schedule grids. Define time slots, manage subject distributions, and assign teachers to avoid conflicts.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-dark border-secondary p-3 h-100">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-2x mb-3">payments</span>
                    <h5 class="text-white font-weight-bold">Fees & Payments</h5>
                    <p class="text-muted">Set up invoices, track payment status, issue receipt PDFs, and allow online/offline payment reconciliations for accountants.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-dark border-secondary p-3 h-100">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-2x mb-3">forum</span>
                    <h5 class="text-white font-weight-bold">Messenger & Collaboration</h5>
                    <p class="text-muted">Direct communication channels allowing school administrators, teachers, and parents to message each other in real-time.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-dark border-secondary p-3 h-100">
                <div class="card-body">
                    <span class="material-symbols-rounded text-info-custom symbol-2x mb-3">support_agent</span>
                    <h5 class="text-white font-weight-bold">Helpdesk Support Tickets</h5>
                    <p class="text-muted">An integrated support ticket system allowing tenant staff to escalate and resolve configuration issues directly with portal operators.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
