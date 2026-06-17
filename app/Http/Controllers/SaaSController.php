<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SaaSController extends Controller
{
    public function landing()
    {
        $plans = Plan::where('active', true)->orderBy('sort_order')->get();
        return view('saas.landing', compact('plans'));
    }

    public function pricing()
    {
        $plans = Plan::where('active', true)->orderBy('sort_order')->get();
        return view('saas.pricing', compact('plans'));
    }

    public function features()
    {
        return view('saas.features');
    }

    public function contact()
    {
        return view('saas.contact');
    }

    public function demoRequest()
    {
        return view('saas.demo');
    }

    public function storeDemoRequest(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'estimated_students' => 'required|integer',
            'message' => 'nullable|string',
        ]);

        // In a real application, you'd send an email or store this in a demo_requests table.
        // We'll log it or fire an event.
        \Illuminate\Support\Facades\Log::info('Demo Request Submitted:', $request->all());

        return back()->with('pop_success', 'Thank you! Your demo request has been submitted. Our team will contact you shortly.');
    }
}
