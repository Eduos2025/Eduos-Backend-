@extends('layouts.saas_master')

@section('page_title', 'Contact Us')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark border-secondary">
                <div class="card-body p-5">
                    <h2 class="text-white font-weight-bold text-center mb-4">Contact Our Support Team</h2>
                    <p class="text-muted text-center mb-5">Have questions about subscription plans, setup assistance, or customized onboarding? Drop us a message.</p>

                    <form action="#" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="text-white">Your Name</label>
                                <input type="text" class="form-control" name="name" required placeholder="John Doe">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="text-white">Email Address</label>
                                <input type="email" class="form-control" name="email" required placeholder="john@example.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="text-white">Subject</label>
                            <input type="text" class="form-control" name="subject" required placeholder="Pricing Inquiry">
                        </div>

                        <div class="form-group">
                            <label class="text-white">Message</label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="Write your message details here..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-info btn-block mt-4">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
