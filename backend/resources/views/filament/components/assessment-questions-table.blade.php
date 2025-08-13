@if (empty($questions))
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
        <div class="text-gray-500 text-lg mb-2">📝</div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No questions added yet</h3>
        <p class="text-gray-600">Go to the "Questions & Options" tab to add questions to this assessment.</p>
    </div>
@else
    <!-- Summary Stats -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-2xl font-bold text-blue-600">{{ count($questions) }}</div>
                <div class="text-sm text-blue-800">Total Questions</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-green-600">{{ array_sum(array_column($questions, 'points')) }}</div>
                <div class="text-sm text-green-800">Total Points</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-purple-600">
                    {{ count($questions) > 0 ? round(array_sum(array_column($questions, 'points')) / count($questions), 1) : 0 }}
                </div>
                <div class="text-sm text-purple-800">Avg Points/Question</div>
            </div>
        </div>
    </div>

    <!-- Questions Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Options
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Correct
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($questions as $index => $question)
                    @php
                        $questionText = $question['question_text'] ?? 'No question text';
                        $points = (int) ($question['points'] ?? 1);
                        $options = $question['options'] ?? [];
                        $sortOrder = (int) ($question['sort_order'] ?? $index + 1);

                        // Sort options by sort_order
                        usort($options, function ($a, $b) {
                            $aOrder = (int) ($a['sort_order'] ?? 999);
                            $bOrder = (int) ($b['sort_order'] ?? 999);
                            return $aOrder <=> $bOrder;
                        });

                        $correctOptions = array_filter($options, fn($opt) => $opt['is_correct'] ?? false);
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                {{ $sortOrder }}
                            </span>
                        </td>

                        <td class="px-4 py-4">
                            <div class="text-sm font-medium text-gray-900 max-w-md">
                                {{ $questionText }}
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            @if (!empty($options))
                                <div class="space-y-1">
                                    @foreach ($options as $optIndex => $option)
                                        @php
                                            $letter = chr(65 + $optIndex);
                                            $optionText = $option['option_text'] ?? 'No text';
                                            $isCorrect = (bool) ($option['is_correct'] ?? false);
                                        @endphp

                                        <div class="flex items-center text-sm">
                                            <span class="font-medium text-gray-700 mr-2">{{ $letter }}.</span>
                                            @if ($isCorrect)
                                                <span class="text-green-600 mr-2">✅</span>
                                            @else
                                                <span class="text-gray-400 mr-2">○</span>
                                            @endif
                                            <span
                                                class="{{ $isCorrect ? 'text-green-800 font-medium' : 'text-gray-600' }}">
                                                {{ Str::limit($optionText, 40) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 text-sm italic">No options</span>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $points }} {{ $points === 1 ? 'point' : 'points' }}
                            </span>
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            @if (!empty($correctOptions))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($correctOptions as $correct)
                                        @php
                                            $correctIndex = array_search($correct, $options);
                                            $correctLetter = $correctIndex !== false ? chr(65 + $correctIndex) : '?';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                            {{ $correctLetter }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-red-500 text-sm">None set</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
