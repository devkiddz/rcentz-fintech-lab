<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome Email',
                'subject' => 'Welcome to Tesla Drives, {{user_name}}!',
                'content' => "Dear {{user_name}},\n\nWelcome to Tesla Drives! We're thrilled to have you join our community of electric vehicle enthusiasts.\n\nAt Tesla Drives, we're committed to providing you with the best selection of Tesla vehicles and exceptional customer service. Whether you're looking for your first electric vehicle or adding to your collection, we're here to help you find the perfect Tesla.\n\nWhat you can expect from us:\n• Premium Tesla vehicles with detailed specifications\n• Secure and flexible payment options including cryptocurrency\n• Professional customer support throughout your journey\n• Transparent pricing with no hidden fees\n\nFeel free to browse our inventory and don't hesitate to reach out if you have any questions. We're here to make your Tesla ownership dream a reality!\n\nBest regards,\nThe Tesla Drives Team",
                'variables' => [],
                'type' => 'welcome',
                'is_active' => true,
            ],
            [
                'name' => 'Purchase Confirmation',
                'subject' => 'Purchase Confirmed - {{car_model}} | Tesla Drives',
                'content' => "Dear {{user_name}},\n\nCongratulations! Your purchase has been confirmed.\n\n**Purchase Details:**\nVehicle: {{car_model}}\nAmount: {{purchase_amount}}\nPayment Method: {{payment_method}}\nOrder ID: {{order_id}}\n\nYour Tesla is now being prepared for delivery. We'll keep you updated throughout the process and notify you as soon as your vehicle is ready.\n\n**What's Next:**\n1. Vehicle preparation and quality inspection\n2. Delivery scheduling (we'll contact you within 2-3 business days)\n3. Final paperwork and key handover\n\nThank you for choosing Tesla Drives. We're excited to help you start your electric journey!\n\nIf you have any questions, please don't hesitate to contact our support team.\n\nBest regards,\nThe Tesla Drives Team",
                'variables' => ['car_model', 'purchase_amount', 'payment_method', 'order_id'],
                'type' => 'purchase',
                'is_active' => true,
            ],
            [
                'name' => 'Payment Reminder',
                'subject' => 'Payment Reminder - Complete Your Tesla Purchase',
                'content' => "Dear {{user_name}},\n\nWe noticed that your payment for the {{car_model}} is still pending. To secure your Tesla and complete your purchase, please complete the payment process.\n\n**Order Details:**\nVehicle: {{car_model}}\nAmount: {{purchase_amount}}\nOrder ID: {{order_id}}\n\nYour Tesla is currently reserved for you, but this reservation will expire in {{expiry_days}} days if payment is not completed.\n\n**To Complete Your Payment:**\n1. Log into your Tesla Drives account\n2. Go to your purchase history\n3. Click \"Complete Payment\" next to your pending order\n\nIf you're experiencing any issues with the payment process or have questions about your order, please contact our support team immediately.\n\nWe're here to help make your Tesla ownership dream a reality!\n\nBest regards,\nThe Tesla Drives Team",
                'variables' => ['car_model', 'purchase_amount', 'order_id', 'expiry_days'],
                'type' => 'reminder',
                'is_active' => true,
            ],
            [
                'name' => 'General Notification',
                'subject' => 'Important Update from Tesla Drives',
                'content' => "Dear {{user_name}},\n\n{{notification_content}}\n\nIf you have any questions or need assistance, please don't hesitate to contact our support team.\n\nThank you for being a valued member of the Tesla Drives community.\n\nBest regards,\nThe Tesla Drives Team",
                'variables' => ['notification_content'],
                'type' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Delivery Scheduled',
                'subject' => 'Your Tesla Delivery is Scheduled - {{car_model}}',
                'content' => "Dear {{user_name}},\n\nGreat news! Your Tesla delivery has been scheduled.\n\n**Delivery Details:**\nVehicle: {{car_model}}\nDelivery Date: {{delivery_date}}\nDelivery Time: {{delivery_time}}\nDelivery Location: {{delivery_location}}\n\n**What to Bring:**\n• Valid driver's license\n• Proof of insurance\n• Payment confirmation (if any balance remaining)\n\n**What to Expect:**\n• Vehicle walkthrough and feature demonstration\n• Final paperwork completion\n• Key handover and Tesla app setup\n• Delivery typically takes 30-45 minutes\n\nWe're excited to hand over the keys to your new Tesla! If you need to reschedule or have any questions, please contact us at least 24 hours before your scheduled delivery.\n\nCongratulations on your new Tesla!\n\nBest regards,\nThe Tesla Drives Team",
                'variables' => ['car_model', 'delivery_date', 'delivery_time', 'delivery_location'],
                'type' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Crypto Payment Instructions',
                'subject' => 'Cryptocurrency Payment Instructions - {{car_model}}',
                'content' => "Dear {{user_name}},\n\nThank you for choosing to pay with cryptocurrency! Below are your payment instructions:\n\n**Payment Details:**\nVehicle: {{car_model}}\nAmount: {{crypto_amount}} {{crypto_currency}}\nWallet Address: {{wallet_address}}\nPayment Deadline: {{payment_deadline}}\n\n**Important Instructions:**\n1. Send the exact amount specified above\n2. Use the wallet address provided (double-check for accuracy)\n3. Submit your transaction hash once payment is sent\n4. Payment must be received within 30 minutes\n\n**Security Notes:**\n• Always verify the wallet address before sending\n• Keep your transaction hash for records\n• Contact us immediately if you encounter any issues\n\nOnce we receive and verify your cryptocurrency payment, we'll send you a confirmation email and begin preparing your Tesla for delivery.\n\nThank you for your purchase!\n\nBest regards,\nThe Tesla Drives Team",
                'variables' => ['car_model', 'crypto_amount', 'crypto_currency', 'wallet_address', 'payment_deadline'],
                'type' => 'purchase',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
