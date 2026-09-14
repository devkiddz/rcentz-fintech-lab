<?php

namespace Database\Seeders;

use App\Models\Car;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cars = [
            [
                'title' => 'Tesla Model S Plaid',
                'description' => 'The ultimate performance sedan with 1,020 horsepower, 0-60 mph in 1.99 seconds, and a 396-mile range. Features advanced autopilot, premium interior, and cutting-edge technology.',
                'make' => 'Tesla',
                'model' => 'Model S',
                'year' => 2024,
                'engine' => 'Tri Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Pearl White',
                'price' => 89990.00,
                'images' => [
                    'https://images.unsplash.com/photo-1617788138017-80ad40651399?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1617788138017-80ad40651399?w=800&h=600&fit=crop&crop=entropy&cs=tinysrgb',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Model 3 Long Range',
                'description' => 'The perfect balance of performance, range, and value. 358-mile range, all-wheel drive, and premium connectivity. The most popular electric sedan worldwide.',
                'make' => 'Tesla',
                'model' => 'Model 3',
                'year' => 2024,
                'engine' => 'Dual Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Midnight Silver',
                'price' => 47240.00,
                'images' => [
                    'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&h=600&fit=crop',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Model Y Performance',
                'description' => 'The versatile SUV for any adventure. 303-mile range, Track Mode, and seating for up to 7. Perfect for families who want performance and practicality.',
                'make' => 'Tesla',
                'model' => 'Model Y',
                'year' => 2024,
                'engine' => 'Dual Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Deep Blue Metallic',
                'price' => 54190.00,
                'images' => [
                    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=600&fit=crop&crop=entropy',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Model X Plaid',
                'description' => 'The ultimate luxury SUV with falcon wing doors, 333-mile range, and seating for up to 7. Experience the future of family transportation.',
                'make' => 'Tesla',
                'model' => 'Model X',
                'year' => 2024,
                'engine' => 'Tri Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Solid Black',
                'price' => 94990.00,
                'images' => [
                    'https://images.unsplash.com/photo-1617704548623-340376564e68?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1617704548623-340376564e68?w=800&h=600&fit=crop&crop=entropy',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Cybertruck Foundation Series',
                'description' => 'The revolutionary pickup truck with ultra-hard 30X cold-rolled stainless steel, bulletproof design, and up to 340 miles of range. Redefining what a truck can be.',
                'make' => 'Tesla',
                'model' => 'Cybertruck',
                'year' => 2024,
                'engine' => 'Tri Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Stainless Steel',
                'price' => 99990.00,
                'images' => [
                    'https://images.unsplash.com/photo-1609630875171-b1321377ee65?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1609630875171-b1321377ee65?w=800&h=600&fit=crop&crop=entropy',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Roadster (Pre-order)',
                'description' => 'The fastest production car ever made. 0-60 mph in 1.9 seconds, 620-mile range, and stunning design. The ultimate expression of Tesla engineering.',
                'make' => 'Tesla',
                'model' => 'Roadster',
                'year' => 2025,
                'engine' => 'Tri Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Cherry Red',
                'price' => 200000.00,
                'images' => [
                    'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=800&h=600&fit=crop&crop=entropy',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Model 3 Standard Range',
                'description' => 'The most affordable Tesla with impressive 272-mile range, rear-wheel drive, and all the essentials of Tesla ownership. Perfect for daily commuting.',
                'make' => 'Tesla',
                'model' => 'Model 3',
                'year' => 2024,
                'engine' => 'Single Motor Rear-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Pearl White',
                'price' => 38990.00,
                'images' => [
                    'https://images.unsplash.com/photo-1536679545554-bf3c00e3d87f?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1536679545554-bf3c00e3d87f?w=800&h=600&fit=crop&crop=entropy',
                ],
                'is_available' => true,
            ],
            [
                'title' => 'Tesla Model Y Long Range',
                'description' => 'The versatile SUV with 330-mile range, all-wheel drive, and spacious interior. Ideal for road trips and everyday adventures.',
                'make' => 'Tesla',
                'model' => 'Model Y',
                'year' => 2024,
                'engine' => 'Dual Motor All-Wheel Drive',
                'transmission' => 'Single Speed',
                'color' => 'Red Multi-Coat',
                'price' => 50490.00,
                'images' => [
                    'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&h=600&fit=crop&crop=entropy',
                ],
                'is_available' => true,
            ],
        ];

        foreach ($cars as $carData) {
            Car::updateOrCreate(
                [
                    'title' => $carData['title'],
                    'make' => $carData['make'],
                    'model' => $carData['model'],
                    'year' => $carData['year'],
                ],
                $carData
            );
        }
    }
}
