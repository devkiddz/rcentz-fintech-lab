<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\StockNews;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FrontendController extends Controller
{
    public function __construct()
    {
        $this->ensureStorageLink();
    }

    /**
     * Ensure storage link exists, create it if it doesn't
     */
    private function ensureStorageLink()
    {
        $linkPath = public_path('storage');
        
        // Check if the storage link already exists
        if (!File::exists($linkPath)) {
            try {
                // Run the storage:link artisan command
                Artisan::call('storage:link');
                
                // info logs suppressed; only log errors
            } catch (\Exception $e) {
                // Log error but don't break the application
                \Log::error('Failed to create storage link: ' . $e->getMessage());
            }
        }
    }

    public function index()
    {
        $featuredCars = Car::where('is_available', true)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Fetch featured market news first; fallback to latest news
        $latestNews = StockNews::getFeaturedNews(6);
        if ($latestNews->isEmpty()) {
            $latestNews = StockNews::orderBy('published_at', 'desc')
                ->limit(6)
                ->get();
        }

        // Stock market snapshots
        $featuredStocks = Stock::active()->featured()->limit(3)->get();
        $gainers = Stock::active()->gainers()->orderBy('change_percentage', 'desc')->limit(5)->get();
        $losers = Stock::active()->losers()->orderBy('change_percentage', 'asc')->limit(5)->get();
        $mostActive = Stock::active()->mostActive()->limit(5)->get();

        return view('frontend.home', compact('featuredCars', 'latestNews', 'featuredStocks', 'gainers', 'losers', 'mostActive'));
    }

    public function show($id)
    {
        $car = Car::findOrFail($id);
        
        // If car is not available, check if current user has purchased it or has pending purchase
        if (!$car->is_available) {
            // If user is not authenticated, deny access
            if (!auth()->check()) {
                abort(404);
            }
            
            // Check if the authenticated user has purchased this car or has a pending purchase
            $userHasPurchaseOrPending = $car->purchases()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['completed', 'pending', 'processing'])
                ->exists();
            
            // If user hasn't purchased this car or doesn't have pending purchase, deny access
            if (!$userHasPurchaseOrPending) {
                abort(404);
            }
        }
        
        return view('frontend.car_detail', compact('car'));
    }

    public function browse(Request $request)
    {
        $query = Car::query();
        
        // Filter by availability (default: show all)
        if ($request->has('available') && $request->available == 'true') {
            $query->where('is_available', true);
        }
        
        // Filter by make
        if ($request->has('make') && !empty($request->make)) {
            $query->where('make', $request->make);
        }
        
        // Filter by model
        if ($request->has('model') && !empty($request->model)) {
            $query->where('model', $request->model);
        }
        
        // Filter by year
        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }
        
        // Sort options
        $sort = $request->sort ?? 'newest';
        
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        // Get unique makes, models and years for filters
        $makes = Car::distinct()->pluck('make')->filter();
        $models = Car::distinct()->pluck('model')->filter();
        $years = Car::distinct()->pluck('year')->filter()->sort()->reverse();
        
        $cars = $query->paginate(12);
        
        return view('frontend.browse', compact('cars', 'makes', 'models', 'years', 'sort'));
    }

    public function about()
    {
        return view('frontend.about');
    }

    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validate the form data
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|in:general,support,billing,partnership,press',
                'message' => 'required|string|min:10|max:2000',
            ], [
                'first_name.required' => 'Please enter your first name.',
                'last_name.required' => 'Please enter your last name.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'subject.required' => 'Please select a subject.',
                'subject.in' => 'Please select a valid subject.',
                'message.required' => 'Please enter your message.',
                'message.min' => 'Your message must be at least 10 characters long.',
                'message.max' => 'Your message cannot exceed 2000 characters.',
            ]);

            try {
                // Send email to site email
                $siteEmail = site_email();
                $subject = "Contact Form: " . ucfirst($validated['subject']);
                
                $emailContent = "
                New contact form submission from {$validated['first_name']} {$validated['last_name']}
                
                Email: {$validated['email']}
                Subject: " . ucfirst($validated['subject']) . "
                
                Message:
                {$validated['message']}
                
                ---
                This message was sent from the contact form on " . site_name() . "
                ";

                // Use Laravel's Mail facade to send the email
                \Mail::raw($emailContent, function($message) use ($siteEmail, $subject, $validated) {
                    $message->to($siteEmail)
                            ->subject($subject)
                            ->replyTo($validated['email'], $validated['first_name'] . ' ' . $validated['last_name']);
                });

                return redirect()->route('contact')->with('success', 'Thank you for your message! We will get back to you soon.');
                
            } catch (\Exception $e) {
                return redirect()->route('contact')
                    ->withErrors(['message' => 'Sorry, there was an error sending your message. Please try again later.'])
                    ->withInput();
            }
        }

        return view('frontend.contact');
    }

    public function helpCenter()
    {
        return view('frontend.help-center');
    }

    public function terms()
    {
        return view('frontend.terms');
    }

    public function privacy()
    {
        return view('frontend.privacy');
    }
}
