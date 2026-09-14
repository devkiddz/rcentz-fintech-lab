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
            Privacy & Legal
          </h1>
          <p class="mt-4 max-w-2xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">
            We are committed to protecting your privacy and ensuring the security of your personal information. Learn how we collect, use, and safeguard your data.
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

  <!-- Privacy Content Section -->
  <section class="bg-white py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <div class="prose prose-lg max-w-none">
        
        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Information We Collect</h2>
          <p class="text-gray-600 mb-4">
            We collect information you provide directly to us, such as when you create an account, complete KYC verification, or use our services:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li><strong>Personal Information:</strong> Name, email address, phone number, date of birth, and government-issued identification</li>
            <li><strong>Financial Information:</strong> Bank account details, cryptocurrency wallet addresses, and transaction history</li>
            <li><strong>Investment Data:</strong> Portfolio holdings, trading history, and investment preferences</li>
            <li><strong>Vehicle Information:</strong> Purchase history, vehicle preferences, and delivery details</li>
            <li><strong>Technical Data:</strong> IP address, device information, and usage analytics</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">How We Use Your Information</h2>
          <p class="text-gray-600 mb-4">
            We use the information we collect to:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Provide, maintain, and improve our services</li>
            <li>Process transactions and manage your account</li>
            <li>Comply with legal and regulatory requirements</li>
            <li>Prevent fraud and ensure platform security</li>
            <li>Communicate with you about our services</li>
            <li>Personalize your experience and provide relevant content</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Information Sharing</h2>
          <p class="text-gray-600 mb-4">
            We do not sell your personal information. We may share your information in the following circumstances:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li><strong>Service Providers:</strong> With trusted third-party vendors who help us operate our platform</li>
            <li><strong>Legal Requirements:</strong> When required by law or to protect our rights and safety</li>
            <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
            <li><strong>Consent:</strong> With your explicit consent for specific purposes</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Data Security</h2>
          <p class="text-gray-600 mb-4">
            We implement comprehensive security measures to protect your information:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Encryption of data in transit and at rest</li>
            <li>Multi-factor authentication for account access</li>
            <li>Regular security audits and penetration testing</li>
            <li>Secure data centers with physical and digital safeguards</li>
            <li>Employee training on data protection practices</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Data Retention</h2>
          <p class="text-gray-600 mb-4">
            We retain your information for as long as necessary to provide our services and comply with legal obligations:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Account information is retained while your account is active</li>
            <li>Transaction records are kept for regulatory compliance (typically 7 years)</li>
            <li>KYC documents are retained as required by financial regulations</li>
            <li>We may retain certain information after account closure for legal purposes</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Your Rights</h2>
          <p class="text-gray-600 mb-4">
            Depending on your location, you may have the following rights regarding your personal information:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li><strong>Access:</strong> Request a copy of your personal information</li>
            <li><strong>Correction:</strong> Update or correct inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your personal information</li>
            <li><strong>Portability:</strong> Receive your data in a portable format</li>
            <li><strong>Objection:</strong> Object to certain processing activities</li>
            <li><strong>Restriction:</strong> Limit how we process your information</li>
          </ul>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Cookies and Tracking</h2>
          <p class="text-gray-600 mb-4">
            We use cookies and similar technologies to enhance your experience:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li><strong>Essential Cookies:</strong> Required for basic platform functionality</li>
            <li><strong>Analytics Cookies:</strong> Help us understand how you use our platform</li>
            <li><strong>Security Cookies:</strong> Protect against fraud and ensure account security</li>
            <li><strong>Preference Cookies:</strong> Remember your settings and preferences</li>
          </ul>
          <p class="text-gray-600 mt-4">
            You can control cookie settings through your browser preferences, though disabling certain cookies may affect platform functionality.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Third-Party Services</h2>
          <p class="text-gray-600 mb-4">
            Our platform may integrate with third-party services:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li><strong>Payment Processors:</strong> For cryptocurrency transactions</li>
            <li><strong>Analytics Services:</strong> To understand platform usage</li>
            <li><strong>Customer Support:</strong> For help desk and communication</li>
            <li><strong>Security Services:</strong> For fraud detection and prevention</li>
          </ul>
          <p class="text-gray-600 mt-4">
            These services have their own privacy policies, and we encourage you to review them.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Children's Privacy</h2>
          <p class="text-gray-600 mb-4">
            Our services are not intended for individuals under 18 years of age. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">International Transfers</h2>
          <p class="text-gray-600 mb-4">
            Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your information in accordance with applicable data protection laws.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Changes to This Policy</h2>
          <p class="text-gray-600 mb-4">
            We may update this Privacy Policy from time to time. We will notify you of any material changes by:
          </p>
          <ul class="list-disc pl-6 text-gray-600 space-y-2">
            <li>Posting the updated policy on our platform</li>
            <li>Sending you an email notification</li>
            <li>Displaying a prominent notice on our website</li>
          </ul>
          <p class="text-gray-600 mt-4">
            Your continued use of our services after changes constitutes acceptance of the updated policy.
          </p>
        </div>

        <div class="mb-12">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-4">Contact Us</h2>
          <p class="text-gray-600 mb-4">
            If you have any questions about this Privacy Policy or our data practices, please contact us:
          </p>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-600 mb-2"><strong>Email:</strong> {{ site_email() }}</p>
            <p class="text-gray-600 mb-2"><strong>Phone:</strong> {{ site_phone() }}</p>
            <p class="text-gray-600 dark:text-gray-300"><strong>Address:</strong> 123 Innovation Drive, Tech City, TC 12345</p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Legal Information Section -->
  <section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Legal Information</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Important legal information about our platform, regulatory compliance, and your rights.
        </p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="bg-white p-6 rounded-lg border border-border">
          <div class="w-12 h-12 bg-black dark:bg-white text-white dark:text-gray-900 rounded-lg flex items-center justify-center mb-4">
            <i data-lucide="shield-check" class="w-6 h-6"></i>
          </div>
          <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Regulatory Compliance</h3>
          <p class="text-gray-600 text-sm">
            We operate in compliance with applicable financial regulations and maintain necessary licenses for our services.
          </p>
        </div>
        
        <div class="bg-white p-6 rounded-lg border border-border">
          <div class="w-12 h-12 text-white rounded-lg flex items-center justify-center mb-4" style="background-color: #C8102E;">
            <i data-lucide="file-text" class="w-6 h-6"></i>
          </div>
          <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Terms of Service</h3>
          <p class="text-gray-600 text-sm">
            Our comprehensive terms of service govern your use of the platform and outline your rights and responsibilities.
          </p>
        </div>
        
        <div class="bg-white p-6 rounded-lg border border-border">
          <div class="w-12 h-12 text-white rounded-lg flex items-center justify-center mb-4" style="background-color: #C8102E;">
            <i data-lucide="gavel" class="w-6 h-6"></i>
          </div>
          <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Dispute Resolution</h3>
          <p class="text-gray-600 text-sm">
            Information about how disputes are resolved and your options for seeking resolution.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="bg-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Questions About Privacy?</h2>
      <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
        Our privacy team is here to help with any questions about how we handle your personal information.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('contact') }}" class="inline-flex items-center rounded-md px-6 py-3 text-sm font-medium text-white shadow-sm transition" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
          <i data-lucide="mail" class="mr-2 h-4 w-4"></i>
          Contact Privacy Team
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
