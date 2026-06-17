@extends('layouts.saas_master')

@section('page_title', 'Request a Demo')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark border-secondary">
                <div class="card-body p-5">
                    <h2 class="text-white font-weight-bold text-center mb-4">Request a Live Demo</h2>
                    <p class="text-muted text-center mb-5">Fill in the form details below. Our product specialists will reach out to schedule a live product tour tailored to your school requirements.</p>

                    <form action="{{ route('saas.demo.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="text-white">School Name</label>
                                <input type="text" class="form-control" name="school_name" required placeholder="Greenwood International School">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="text-white">Contact Person Name</label>
                                <input type="text" class="form-control" name="contact_name" required placeholder="Mr. Adams">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="text-white">Email Address</label>
                                <input type="email" class="form-control" name="email" required placeholder="adams@greenwood.com">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="text-white">Phone Number</label>
                                <input type="text" class="form-control" name="phone" required placeholder="+234 803 123 4567">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="text-white">Estimated Number of Students</label>
                            <input type="number" class="form-control" name="estimated_students" required placeholder="350">
                        </div>

                        <div class="form-group">
                            <label class="text-white">Additional Notes / Custom Requirements</label>
                            <textarea class="form-control" name="message" rows="4" placeholder="Any specific modules or integrations you are looking to deploy..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-info btn-block mt-4">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
