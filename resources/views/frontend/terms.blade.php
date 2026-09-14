@extends('layouts.main')

@section('content')
<div class="bg-tesla-50 dark:bg-dark-bg">
  <!-- Hero Section -->
  <header class="relative overflow-hidden bg-gradient-to-br from-black via-gray-900 to-black text-white">
    <svg class="absolute inset-0 opacity-[0.06] z-0 pointer-events-none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true" focusable="false">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)" />
    </svg>

    <div class="absolute inset-0 z-0 pointer-events-none" aria-hidden="true">
      <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black/90"></div>
    </div>

    <div class="relative z-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="text-center">
          <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
            Terms of Service
          </h1>
          <p class="mt-4 max-w-2xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">
            Please read these terms carefully before using our platform. By accessing or using our services, you agree to be bound by these terms.
          </p>
          <p class="mt-2 text-sm text-gray-400 dark:text-gray-300">Last updated: {{ date('F j, Y') }}</p>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 translate-y-[1px]" aria-hidden="true">
      <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg">
        <path fill="#0b0b0b" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,53.3C672,32,768,0,864,0C960,0,1056,32,1152,53.3C1248,75,1344,85,1392,90.7L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1050,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z"></path>
      </svg>
    </div>
  </header>

  <!-- Terms Content Section -->
  <section class="bg-white py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <div class="prose prose-lg max-w-none">
        
        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">1. Acceptance of Terms</h2>
          <p class="text-gray-600 mb-4">
            By accessing and using {{ site_name() }} ("the Platform"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">2. Description of Service</h2>
          <p class="text-gray-600 mb-4">
            {{ site_name() }} provides an all-in-one platform for cryptocurrency wallet management, automated investments, stock trading, and premium electric vehicle marketplace. Our services include:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Digital wallet services for cryptocurrency storage and transactions</li>
            <li>Automated investment plans and portfolio management</li>
            <li>Real-time stock trading and market analysis tools</li>
            <li>Premium electric vehicle marketplace and purchase services</li>
            <li>KYC verification and compliance services</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">3. User Accounts and Registration</h2>
          <p class="text-gray-600 mb-4">
            To access certain features of the Platform, you must register for an account. You agree to:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Provide accurate, current, and complete information during registration</li>
            <li>Maintain and promptly update your account information</li>
            <li>Maintain the security of your account credentials</li>
            <li>Accept responsibility for all activities under your account</li>
            <li>Complete KYC verification as required by applicable regulations</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">4. Investment and Trading Services</h2>
          <p class="text-gray-600 mb-4">
            Our investment and trading services are subject to the following terms:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>All investments carry risk, and past performance does not guarantee future results</li>
            <li>You are responsible for understanding the risks associated with your investments</li>
            <li>We do not provide financial advice - all investment decisions are your own</li>
            <li>Trading fees and commissions apply as disclosed in our fee schedule</li>
            <li>Market data is provided for informational purposes only</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">5. Cryptocurrency Services</h2>
          <p class="text-gray-600 mb-4">
            Our cryptocurrency wallet and payment services are governed by:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Cryptocurrency values are volatile and subject to market fluctuations</li>
            <li>We support specific cryptocurrencies as listed on our platform</li>
            <li>Transaction fees apply for deposits and withdrawals</li>
            <li>We implement security measures but cannot guarantee against all risks</li>
            <li>You are responsible for maintaining secure access to your wallet</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">6. Vehicle Marketplace</h2>
          <p class="text-gray-600 mb-4">
            Our vehicle marketplace services include:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Vehicle listings are provided by third-party sellers</li>
            <li>We facilitate transactions but are not the seller of vehicles</li>
            <li>Vehicle availability and pricing are subject to change</li>
            <li>All vehicle purchases are subject to additional terms and conditions</li>
            <li>Delivery and registration processes vary by location</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">7. Fees and Payments</h2>
          <p class="text-gray-600 mb-4">
            You agree to pay all applicable fees for services used:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Trading commissions and fees as disclosed</li>
            <li>Wallet transaction fees for deposits and withdrawals</li>
            <li>Investment plan management fees</li>
            <li>Vehicle marketplace transaction fees</li>
            <li>Fees are subject to change with notice</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">8. Privacy and Data Protection</h2>
          <p class="text-gray-600 mb-4">
            Your privacy is important to us. Our collection and use of personal information is governed by our Privacy Policy, which is incorporated into these Terms by reference.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">9. Security and Compliance</h2>
          <p class="text-gray-600 mb-4">
            We implement security measures to protect your information and assets:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>We comply with applicable financial regulations and KYC requirements</li>
            <li>We use industry-standard security protocols</li>
            <li>You are responsible for maintaining secure access to your account</li>
            <li>We may suspend accounts for security concerns</li>
            <li>We cooperate with law enforcement when required by law</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">10. Prohibited Activities</h2>
          <p class="text-gray-600 mb-4">
            You agree not to:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Use the Platform for illegal activities or fraud</li>
            <li>Attempt to gain unauthorized access to our systems</li>
            <li>Interfere with the proper operation of the Platform</li>
            <li>Provide false or misleading information</li>
            <li>Violate any applicable laws or regulations</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">11. Limitation of Liability</h2>
          <p class="text-gray-600 mb-4">
            To the maximum extent permitted by law, {{ site_name() }} shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or use, incurred by you or any third party.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">12. Termination</h2>
          <p class="text-gray-600 mb-4">
            We may terminate or suspend your account at any time for violations of these Terms. You may terminate your account at any time by contacting our support team.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">13. Changes to Terms</h2>
          <p class="text-gray-600 mb-4">
            We reserve the right to modify these Terms at any time. We will notify users of material changes via email or through the Platform. Your continued use of the Platform after changes constitutes acceptance of the new Terms.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">14. Governing Law</h2>
          <p class="text-gray-600 mb-4">
            These Terms are governed by and construed in accordance with the laws of the jurisdiction in which {{ site_name() }} operates, without regard to conflict of law principles.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">15. Contact Information</h2>
          <p class="text-gray-600 mb-4">
            If you have any questions about these Terms, please contact us at {{ site_email() }}.
          </p>
        </div>

      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Questions About These Terms?</h2>
      <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
        If you have any questions about our Terms of Service, our legal team is here to help clarify any concerns.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('contact') }}" class="inline-flex items-center rounded-md px-6 py-3 text-sm font-medium text-white shadow-sm transition" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
          <i data-lucide="mail" class="mr-2 h-4 w-4"></i>
          Contact Legal Team
        </a>
        <a href="{{ route('help-center') }}" class="inline-flex items-center rounded-md border border-gray-300 px-6 py-3 text-sm font-medium text-black transition hover:bg-gray-50">
          <i data-lucide="help-circle" class="mr-2 h-4 w-4"></i>
          Help Center
        </a>
      </div>
    </div>
  </section>
</div>
@endsection
