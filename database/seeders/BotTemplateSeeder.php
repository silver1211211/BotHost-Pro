<?php

namespace Database\Seeders;

use App\Models\BotTemplate;
use Illuminate\Database\Seeder;

class BotTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome Bot',
                'slug' => 'welcome-bot',
                'description' => 'A simple starter bot with welcome, help, and about commands.',
                'category' => 'starter',
                'tags' => ['welcome', 'starter'],
                'commands' => [
                    ['/start', 'Welcome! Use /help to see available commands.'],
                    ['/help', 'Available commands: /start, /help, /about'],
                    ['/about', 'This bot was created with BotHost Pro.'],
                ],
            ],
            [
                'name' => 'Support Bot',
                'slug' => 'support-bot',
                'description' => 'Common support commands for customer help and FAQs.',
                'category' => 'support',
                'tags' => ['support', 'faq'],
                'commands' => [
                    ['/start', 'Welcome to support. Use /help to see options.'],
                    ['/help', 'Available commands: /support, /faq'],
                    ['/support', 'Please describe your issue and our team will help.'],
                    ['/faq', 'FAQ: Check your order, payment, and account details from your dashboard.'],
                ],
            ],
            [
                'name' => 'Promo Bot',
                'slug' => 'promo-bot',
                'description' => 'Promotional commands for offers and useful links.',
                'category' => 'marketing',
                'tags' => ['promo', 'links'],
                'commands' => [
                    ['/start', 'Welcome! Use /offer to see our latest promotion.'],
                    ['/offer', 'Today’s offer is live. Check back often for updates.'],
                    ['/links', 'Useful links: website, support, and community.'],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $template = BotTemplate::updateOrCreate(
                ['slug' => $templateData['slug']],
                [
                    'name' => $templateData['name'],
                    'description' => $templateData['description'],
                    'category' => $templateData['category'],
                    'level' => 'beginner',
                    'status' => 'published',
                    'is_featured' => true,
                    'tags' => $templateData['tags'],
                    'published_at' => now(),
                    'commands_count' => count($templateData['commands']),
                ],
            );

            foreach ($templateData['commands'] as $index => [$commandName, $responseText]) {
                $template->commands()->updateOrCreate(
                    ['command_name' => $commandName],
                    [
                        'response_text' => $responseText,
                        'code' => null,
                        'runtime' => 'node',
                        'language' => 'javascript',
                        'status' => 'active',
                        'sort_order' => $index,
                    ],
                );
            }

            $template->forceFill(['commands_count' => $template->commands()->count()])->save();
        }
    }
}
