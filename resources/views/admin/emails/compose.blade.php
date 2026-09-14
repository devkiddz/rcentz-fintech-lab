<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ __('Send Email to Users') }}
                </h2>
               </div>
            <a href="{{ route('admin.emails.index') }}" 
               class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Templates
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('admin.emails.send') }}" method="POST" id="email-form">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Email Composition -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Template Selection -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Select Email Template</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label for="template_id" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Email Template *
                                    </label>
                                    <select name="template_id" id="template_id" required
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                        <option value="">Choose a template...</option>
                                        <option value="custom" data-subject="" data-content="" data-variables="[]">
                                            📝 Custom Message (Type your own)
                                        </option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}" 
                                                    data-subject="{{ $template->subject }}"
                                                    data-content="{{ $template->content }}"
                                                    data-variables="{{ json_encode($template->variables) }}">
                                                {{ $template->name }} ({{ ucfirst($template->type) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('template_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Custom Template Fields -->
                                <div id="custom-template-section" class="hidden space-y-3">
                                    <div>
                                        <label for="custom_subject" class="block text-xs font-medium text-muted-foreground mb-2">
                                            Email Subject *
                                        </label>
                                        <input type="text" name="custom_subject" id="custom_subject"
                                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black"
                                               placeholder="Enter your email subject...">
                                    </div>
                                    
                                    <div>
                                        <label for="custom_content" class="block text-xs font-medium text-muted-foreground mb-2">
                                            Email Content *
                                        </label>
                                        <div class="border border-border rounded-lg">
                                            <div class="bg-muted/40 px-3 py-2 border-b border-border flex items-center space-x-2 text-xs">
                                                <button type="button" onclick="formatText('bold')" class="px-2 py-1 bg-card text-foreground border border-border rounded hover:bg-muted">
                                                    <strong>B</strong>
                                                </button>
                                                <button type="button" onclick="formatText('italic')" class="px-2 py-1 bg-card text-foreground border border-border rounded hover:bg-muted">
                                                    <em>I</em>
                                                </button>
                                                <button type="button" onclick="formatText('underline')" class="px-2 py-1 bg-card text-foreground border border-border rounded hover:bg-muted">
                                                    <u>U</u>
                                                </button>
                                                <span class="text-gray-400 dark:text-gray-300">|</span>
                                                <button type="button" onclick="insertVariable('user_name')" class="px-2 py-1 bg-tesla-50 text-tesla-700 border border-tesla-300 rounded hover:bg-tesla-100 text-xs">
                                                    + Name
                                                </button>
                                                <button type="button" onclick="insertVariable('user_email')" class="px-2 py-1 bg-tesla-50 text-tesla-700 border border-tesla-300 rounded hover:bg-tesla-100 text-xs">
                                                    + Email
                                                </button>
                                            </div>
                                            <textarea name="custom_content" id="custom_content" rows="10"
                                                      class="w-full px-3 py-2 border-0 rounded-b-lg focus:ring-2 focus:ring-ring focus:border-black resize-none"
                                                      placeholder="Type your message here...

You can use:
- **Bold text**
- *Italic text*
- _Underlined text_
- @{{user_name}} for recipient's name
- @{{user_email}} for recipient's email"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Preview -->
                                <div id="template-preview" class="hidden">
                                    <h4 class="text-sm font-medium text-foreground dark:text-white mb-2">Template Preview</h4>
                                    <div class="bg-muted/40 border border-border dark:border-gray-700 rounded-lg p-3">
                                        <div class="mb-2">
                                            <strong class="text-xs text-muted-foreground dark:text-gray-300">Subject:</strong>
                                            <span id="preview-subject" class="text-sm text-foreground ml-2"></span>
                                        </div>
                                        <div>
                                            <strong class="text-xs text-muted-foreground dark:text-gray-300">Content:</strong>
                                            <div id="preview-content" class="text-sm text-foreground mt-1 prose prose-sm max-w-none"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Content -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Email Content</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label for="subject" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Email Subject *
                                    </label>
                                    <input type="text" name="subject" id="subject" required
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black"
                                           placeholder="Enter email subject...">
                                    @error('subject')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="content" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Email Content *
                                    </label>
                                    <div class="border border-border rounded-lg">
                                        <div class="bg-muted/40 px-3 py-2 border-b border-border flex items-center space-x-2 text-xs">
                                            <button type="button" onclick="formatText('bold')" class="px-2 py-1 bg-card text-foreground border border-border rounded hover:bg-muted">
                                                <strong>B</strong>
                                            </button>
                                            <button type="button" onclick="formatText('italic')" class="px-2 py-1 bg-card text-foreground border border-border rounded hover:bg-muted">
                                                <em>I</em>
                                            </button>
                                            <button type="button" onclick="formatText('underline')" class="px-2 py-1 bg-card text-foreground border border-border rounded hover:bg-muted">
                                                <u>U</u>
                                            </button>
                                            <span class="text-gray-400 dark:text-gray-300">|</span>
                                            <button type="button" onclick="insertVariable('user_name')" class="px-2 py-1 bg-tesla-50 text-tesla-700 border border-tesla-300 rounded hover:bg-tesla-100 text-xs">
                                                + Name
                                            </button>
                                            <button type="button" onclick="insertVariable('user_email')" class="px-2 py-1 bg-tesla-50 text-tesla-700 border border-tesla-300 rounded hover:bg-tesla-100 text-xs">
                                                + Email
                                            </button>
                                        </div>
                                        <textarea name="content" id="content" rows="12" required
                                                  class="w-full px-3 py-2 border-0 rounded-b-lg focus:ring-2 focus:ring-ring focus:border-black resize-none"
                                                  placeholder="Enter your email content here...

You can use:
- **Bold text**
- *Italic text*
- _Underlined text_
- @{{user_name}} for recipient's name
- @{{user_email}} for recipient's email"></textarea>
                                    </div>
                                    @error('content')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recipients Selection -->
                    <div class="space-y-4">
                        <!-- Recipients -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Select Recipients</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Recipient Type *</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="recipient_type" value="all" class="text-foreground focus:ring-ring" checked>
                                            <span class="ml-2 text-sm text-foreground">All Users</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="recipient_type" value="custom" class="text-foreground focus:ring-ring">
                                            <span class="ml-2 text-sm text-foreground">Custom Selection</span>
                                        </label>
                                    </div>
                                </div>

                                <div id="custom-recipients" class="hidden">
                                    <label for="recipients" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Select Users
                                    </label>
                                    <select name="recipients[]" id="recipients" multiple
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-muted-foreground mt-1">Hold Ctrl/Cmd to select multiple users</p>
                                </div>

                                <div class="bg-tesla-50 border border-tesla-200 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-tesla-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="text-xs text-tesla-800">
                                            <strong>Recipient Count:</strong>
                                            <span id="recipient-count" class="ml-1">{{ $users->count() }}</span> users will receive this email.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Send Options -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Send Options</h3>
                            
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="send_immediately" value="1" class="text-foreground focus:ring-ring" checked>
                                    <span class="ml-2 text-sm text-foreground">Send immediately</span>
                                </label>
                                
                                <div id="schedule-section" class="hidden">
                                    <label for="scheduled_at" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Schedule for
                                    </label>
                                    <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                </div>

                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-yellow-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <div class="text-xs text-yellow-800">
                                            <strong>Important:</strong> Emails will be sent to all selected recipients. Make sure to test with a small group first.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Send Button -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Send Email
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Template selection handling
        document.getElementById('template_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const customSection = document.getElementById('custom-template-section');
            const preview = document.getElementById('template-preview');
            
            if (this.value === 'custom') {
                customSection.classList.remove('hidden');
                preview.classList.add('hidden');
            } else if (this.value) {
                customSection.classList.add('hidden');
                preview.classList.remove('hidden');
                
                // Update preview
                document.getElementById('preview-subject').textContent = selectedOption.dataset.subject;
                document.getElementById('preview-content').innerHTML = selectedOption.dataset.content;
                
                // Update form fields
                document.getElementById('subject').value = selectedOption.dataset.subject;
                document.getElementById('content').value = selectedOption.dataset.content;
            } else {
                customSection.classList.add('hidden');
                preview.classList.add('hidden');
            }
        });

        // Recipient type handling
        document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const customRecipients = document.getElementById('custom-recipients');
                const recipientCount = document.getElementById('recipient-count');
                
                if (this.value === 'custom') {
                    customRecipients.classList.remove('hidden');
                    recipientCount.textContent = '0';
                } else {
                    customRecipients.classList.add('hidden');
                    recipientCount.textContent = '{{ $users->count() }}';
                }
            });
        });

        // Custom recipients selection
        document.getElementById('recipients').addEventListener('change', function() {
            const recipientCount = document.getElementById('recipient-count');
            recipientCount.textContent = this.selectedOptions.length;
        });

        // Schedule handling
        document.querySelector('input[name="send_immediately"]').addEventListener('change', function() {
            const scheduleSection = document.getElementById('schedule-section');
            if (!this.checked) {
                scheduleSection.classList.remove('hidden');
            } else {
                scheduleSection.classList.add('hidden');
            }
        });

        // Text formatting functions
        function formatText(type) {
            const textarea = document.getElementById('content');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            let formattedText = '';
            switch(type) {
                case 'bold':
                    formattedText = `**${selectedText}**`;
                    break;
                case 'italic':
                    formattedText = `*${selectedText}*`;
                    break;
                case 'underline':
                    formattedText = `_${selectedText}_`;
                    break;
            }
            
            textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
        }

        function insertVariable(variable) {
            const textarea = document.getElementById('content');
            const variableText = `@{{${variable}}}`;
            const cursorPos = textarea.selectionStart;
            
            textarea.value = textarea.value.substring(0, cursorPos) + variableText + textarea.value.substring(cursorPos);
            textarea.focus();
            textarea.setSelectionRange(cursorPos + variableText.length, cursorPos + variableText.length);
        }
    </script>
</x-admin-layout> 
