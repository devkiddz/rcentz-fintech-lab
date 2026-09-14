<x-guest-layout>
    <x-slot name="title">Create Account</x-slot>
    <x-slot name="wide">true</x-slot>

    <section class="ui-panel overflow-hidden">
        <div class="px-6 py-6 text-center sm:px-8">
            <h1 class="text-xl font-semibold text-foreground">Create account</h1>
            <p class="mt-1 text-xs text-muted-foreground">Create your {{ site_name() }} account.</p>
        </div>
        <form method="POST" action="{{ route('register') }}" class="grid gap-4 px-6 pb-6 sm:grid-cols-2 sm:px-8">
            @csrf
            <div class="sm:col-span-2">
                <label for="name" class="ui-label">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="ui-input @error('name') border-red-500 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="ui-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="ui-input @error('email') border-red-500 @enderror">
                @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="ui-label">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="ui-input @error('password') border-red-500 @enderror">
                @error('password')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="ui-label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="ui-input">
            </div>
            <div>
                <label for="country" class="ui-label">Country</label>
                <select id="country" name="country" class="ui-input @error('country') border-red-500 @enderror"><option value="">Select country</option></select>
                @error('country')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="currency" class="ui-label">Preferred currency</label>
                <select id="currency" name="currency" class="ui-input @error('currency') border-red-500 @enderror">
                    @foreach(['USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound', 'NGN' => 'Nigerian Naira', 'JPY' => 'Japanese Yen', 'AUD' => 'Australian Dollar', 'CAD' => 'Canadian Dollar', 'CHF' => 'Swiss Franc', 'CNY' => 'Chinese Yuan', 'INR' => 'Indian Rupee', 'ZAR' => 'South African Rand', 'SGD' => 'Singapore Dollar'] as $code => $label)
                        <option value="{{ $code }}" {{ old('currency', 'USD') === $code ? 'selected' : '' }}>{{ $code }} — {{ $label }}</option>
                    @endforeach
                </select>
                @error('currency')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2 pt-1"><button type="submit" class="ui-button-primary w-full">Create account</button></div>
        </form>
        <div class="border-t border-border bg-muted/30 px-6 py-4 text-center text-xs text-muted-foreground">
            Already have an account? <a href="{{ route('login') }}" class="font-semibold text-tesla-600 dark:text-tesla-400">Sign in</a>
        </div>
    </section>

    <div class="my-5 flex items-center gap-3 text-xs text-muted-foreground"><div class="h-px flex-1 bg-border"></div><span>or</span><div class="h-px flex-1 bg-border"></div></div>
    <a href="{{ route('google.redirect') }}" class="ui-button-secondary w-full">Continue with Google</a>
    <p class="mt-5 text-center text-[11px] text-muted-foreground">By creating an account, you agree to our <a href="{{ route('terms') }}" class="text-tesla-600 dark:text-tesla-400">Terms</a> and <a href="{{ route('privacy') }}" class="text-tesla-600 dark:text-tesla-400">Privacy Policy</a>.</p>

    <script>
        // Populate countries dropdown immediately on page load
        document.addEventListener('DOMContentLoaded', function() {
            // List of countries
            const countries = [
            {code: "AF", name: "Afghanistan"}, {code: "AL", name: "Albania"}, {code: "DZ", name: "Algeria"},
            {code: "AS", name: "American Samoa"}, {code: "AD", name: "Andorra"}, {code: "AO", name: "Angola"},
            {code: "AI", name: "Anguilla"}, {code: "AG", name: "Antigua and Barbuda"}, {code: "AR", name: "Argentina"},
            {code: "AM", name: "Armenia"}, {code: "AW", name: "Aruba"}, {code: "AU", name: "Australia"},
            {code: "AT", name: "Austria"}, {code: "AZ", name: "Azerbaijan"}, {code: "BS", name: "Bahamas"},
            {code: "BH", name: "Bahrain"}, {code: "BD", name: "Bangladesh"}, {code: "BB", name: "Barbados"},
            {code: "BY", name: "Belarus"}, {code: "BE", name: "Belgium"}, {code: "BZ", name: "Belize"},
            {code: "BJ", name: "Benin"}, {code: "BM", name: "Bermuda"}, {code: "BT", name: "Bhutan"},
            {code: "BO", name: "Bolivia"}, {code: "BA", name: "Bosnia and Herzegovina"}, {code: "BW", name: "Botswana"},
            {code: "BR", name: "Brazil"}, {code: "BN", name: "Brunei"}, {code: "BG", name: "Bulgaria"},
            {code: "BF", name: "Burkina Faso"}, {code: "BI", name: "Burundi"}, {code: "KH", name: "Cambodia"},
            {code: "CM", name: "Cameroon"}, {code: "CA", name: "Canada"}, {code: "CV", name: "Cape Verde"},
            {code: "KY", name: "Cayman Islands"}, {code: "CF", name: "Central African Republic"}, {code: "TD", name: "Chad"},
            {code: "CL", name: "Chile"}, {code: "CN", name: "China"}, {code: "CO", name: "Colombia"},
            {code: "KM", name: "Comoros"}, {code: "CG", name: "Congo"}, {code: "CR", name: "Costa Rica"},
            {code: "HR", name: "Croatia"}, {code: "CU", name: "Cuba"}, {code: "CY", name: "Cyprus"},
            {code: "CZ", name: "Czech Republic"}, {code: "DK", name: "Denmark"}, {code: "DJ", name: "Djibouti"},
            {code: "DM", name: "Dominica"}, {code: "DO", name: "Dominican Republic"}, {code: "EC", name: "Ecuador"},
            {code: "EG", name: "Egypt"}, {code: "SV", name: "El Salvador"}, {code: "GQ", name: "Equatorial Guinea"},
            {code: "ER", name: "Eritrea"}, {code: "EE", name: "Estonia"}, {code: "ET", name: "Ethiopia"},
            {code: "FJ", name: "Fiji"}, {code: "FI", name: "Finland"}, {code: "FR", name: "France"},
            {code: "GA", name: "Gabon"}, {code: "GM", name: "Gambia"}, {code: "GE", name: "Georgia"},
            {code: "DE", name: "Germany"}, {code: "GH", name: "Ghana"}, {code: "GR", name: "Greece"},
            {code: "GD", name: "Grenada"}, {code: "GT", name: "Guatemala"}, {code: "GN", name: "Guinea"},
            {code: "GW", name: "Guinea-Bissau"}, {code: "GY", name: "Guyana"}, {code: "HT", name: "Haiti"},
            {code: "HN", name: "Honduras"}, {code: "HK", name: "Hong Kong"}, {code: "HU", name: "Hungary"},
            {code: "IS", name: "Iceland"}, {code: "IN", name: "India"}, {code: "ID", name: "Indonesia"},
            {code: "IR", name: "Iran"}, {code: "IQ", name: "Iraq"}, {code: "IE", name: "Ireland"},
            {code: "IL", name: "Israel"}, {code: "IT", name: "Italy"}, {code: "JM", name: "Jamaica"},
            {code: "JP", name: "Japan"}, {code: "JO", name: "Jordan"}, {code: "KZ", name: "Kazakhstan"},
            {code: "KE", name: "Kenya"}, {code: "KI", name: "Kiribati"}, {code: "KP", name: "North Korea"},
            {code: "KR", name: "South Korea"}, {code: "KW", name: "Kuwait"}, {code: "KG", name: "Kyrgyzstan"},
            {code: "LA", name: "Laos"}, {code: "LV", name: "Latvia"}, {code: "LB", name: "Lebanon"},
            {code: "LS", name: "Lesotho"}, {code: "LR", name: "Liberia"}, {code: "LY", name: "Libya"},
            {code: "LI", name: "Liechtenstein"}, {code: "LT", name: "Lithuania"}, {code: "LU", name: "Luxembourg"},
            {code: "MO", name: "Macau"}, {code: "MK", name: "Macedonia"}, {code: "MG", name: "Madagascar"},
            {code: "MW", name: "Malawi"}, {code: "MY", name: "Malaysia"}, {code: "MV", name: "Maldives"},
            {code: "ML", name: "Mali"}, {code: "MT", name: "Malta"}, {code: "MH", name: "Marshall Islands"},
            {code: "MR", name: "Mauritania"}, {code: "MU", name: "Mauritius"}, {code: "MX", name: "Mexico"},
            {code: "FM", name: "Micronesia"}, {code: "MD", name: "Moldova"}, {code: "MC", name: "Monaco"},
            {code: "MN", name: "Mongolia"}, {code: "ME", name: "Montenegro"}, {code: "MA", name: "Morocco"},
            {code: "MZ", name: "Mozambique"}, {code: "MM", name: "Myanmar"}, {code: "NA", name: "Namibia"},
            {code: "NR", name: "Nauru"}, {code: "NP", name: "Nepal"}, {code: "NL", name: "Netherlands"},
            {code: "NZ", name: "New Zealand"}, {code: "NI", name: "Nicaragua"}, {code: "NE", name: "Niger"},
            {code: "NG", name: "Nigeria"}, {code: "NO", name: "Norway"}, {code: "OM", name: "Oman"},
            {code: "PK", name: "Pakistan"}, {code: "PW", name: "Palau"}, {code: "PS", name: "Palestine"},
            {code: "PA", name: "Panama"}, {code: "PG", name: "Papua New Guinea"}, {code: "PY", name: "Paraguay"},
            {code: "PE", name: "Peru"}, {code: "PH", name: "Philippines"}, {code: "PL", name: "Poland"},
            {code: "PT", name: "Portugal"}, {code: "PR", name: "Puerto Rico"}, {code: "QA", name: "Qatar"},
            {code: "RO", name: "Romania"}, {code: "RU", name: "Russia"}, {code: "RW", name: "Rwanda"},
            {code: "KN", name: "Saint Kitts and Nevis"}, {code: "LC", name: "Saint Lucia"}, {code: "VC", name: "Saint Vincent"},
            {code: "WS", name: "Samoa"}, {code: "SM", name: "San Marino"}, {code: "ST", name: "Sao Tome and Principe"},
            {code: "SA", name: "Saudi Arabia"}, {code: "SN", name: "Senegal"}, {code: "RS", name: "Serbia"},
            {code: "SC", name: "Seychelles"}, {code: "SL", name: "Sierra Leone"}, {code: "SG", name: "Singapore"},
            {code: "SK", name: "Slovakia"}, {code: "SI", name: "Slovenia"}, {code: "SB", name: "Solomon Islands"},
            {code: "SO", name: "Somalia"}, {code: "ZA", name: "South Africa"}, {code: "SS", name: "South Sudan"},
            {code: "ES", name: "Spain"}, {code: "LK", name: "Sri Lanka"}, {code: "SD", name: "Sudan"},
            {code: "SR", name: "Suriname"}, {code: "SZ", name: "Swaziland"}, {code: "SE", name: "Sweden"},
            {code: "CH", name: "Switzerland"}, {code: "SY", name: "Syria"}, {code: "TW", name: "Taiwan"},
            {code: "TJ", name: "Tajikistan"}, {code: "TZ", name: "Tanzania"}, {code: "TH", name: "Thailand"},
            {code: "TL", name: "Timor-Leste"}, {code: "TG", name: "Togo"}, {code: "TO", name: "Tonga"},
            {code: "TT", name: "Trinidad and Tobago"}, {code: "TN", name: "Tunisia"}, {code: "TR", name: "Turkey"},
            {code: "TM", name: "Turkmenistan"}, {code: "TV", name: "Tuvalu"}, {code: "UG", name: "Uganda"},
            {code: "UA", name: "Ukraine"}, {code: "AE", name: "United Arab Emirates"}, {code: "GB", name: "United Kingdom"},
            {code: "US", name: "United States"}, {code: "UY", name: "Uruguay"}, {code: "UZ", name: "Uzbekistan"},
            {code: "VU", name: "Vanuatu"}, {code: "VA", name: "Vatican City"}, {code: "VE", name: "Venezuela"},
            {code: "VN", name: "Vietnam"}, {code: "YE", name: "Yemen"}, {code: "ZM", name: "Zambia"},
            {code: "ZW", name: "Zimbabwe"}
        ];

            // Populate countries dropdown
            const countrySelect = document.getElementById('country');
            const oldCountry = '{{ old("country") }}';
            
            countries.forEach(country => {
                const option = document.createElement('option');
                option.value = country.code;
                option.textContent = country.name;
                if (country.code === oldCountry) {
                    option.selected = true;
                }
                countrySelect.appendChild(option);
            });
        });
    </script>
</x-guest-layout>
