<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    private array $models = [
        'gemini-3.6-flash',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-3.1-flash-lite',
    ];

    // generate fun to seperate the request to Gemini API and handle errors and retries
    // from the prompt and schema and return the result as an array
    private function generate(string $prompt, array $schema): array
    {
        foreach ($this->models as $model) {
            $response = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    $response = Http::withHeaders([
                        'x-goog-api-key' => config('services.gemini.api_key'),
                        'Content-Type' => 'application/json',
                    ])->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        [
                            'contents' => [
                                [
                                    'role' => 'user',
                                    'parts' => [
                                        [
                                            'text' => $prompt,
                                        ],
                                    ],
                                ],
                            ],
                            'generationConfig' => [
                                'responseMimeType' => 'application/json',
                                'responseJsonSchema' => $schema,
                            ],
                        ]
                    );
                } catch (ConnectionException $e) {
                    if ($attempt === 3) {
                        break;
                    }
                    sleep($attempt * 2);

                    continue;
                }

                if ($response->successful()) {
                    break;
                }

                if ($response->status() == 503 && $attempt < 3) {
                    sleep($attempt * 2);

                    continue;

                }

                break;
            }
            if ($response && $response->successful()) {
                return $this->parseResponse($response);
            }
            \Log::warning("Model {$model} failed with status "
                .($response?->status() ?? 'connection error')
                .', trying next model.'
            );
        }
        throw new \Exception('All Gemini models failed or exceeded their quota.');
    }

    private function parseResponse($response): array
    {
        $text = $response->json('candidates.0.content.parts.0.text');

        if (! $text) {
            throw new \Exception('Gemini returned an empty response.');
        }

        $result = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON returned by Gemini: '.json_last_error_msg());
        }

        return $result;
    }

    public function analyze(Project $project): array
    {
        $prompt = $this->buildAnalysisPrompt($project);

        return $this->generate($prompt, $this->analysisSchema());
    }

    private function buildAnalysisPrompt(Project $project): string
    {
        return <<<PROMPT
        You are an expert startup and product validation analyst.

        Analyze the following project idea objectively.

        Project name:
        {$project->name}

        Description:
        {$project->description}

        Target audience:
        {$project->target_audience}

        Industry:
        {$project->industry}

        Evaluate the idea based on:
        - Problem severity
        - Target audience fit
        - Value proposition
        - Feasibility
        - Differentiation

        Return practical and honest feedback.
        Do not invent market facts that were not provided.
        Scores must be integers from 0 to 100.

        PROMPT;
    }

    private function analysisSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'problem_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'target_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'value_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'feasibility_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'differentiation_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'overall_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'summary' => [
                    'type' => 'string',
                ],
                'strengths' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'weaknesses' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'risks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'level' => [
                                'type' => 'string',
                            ],
                            'description' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'level',
                            'description',
                        ],
                    ],
                ],
            ],
            'required' => [
                'problem_score',
                'target_score',
                'value_score',
                'feasibility_score',
                'differentiation_score',
                'overall_score',
                'summary',
                'strengths',
                'weaknesses',
                'risks',
            ],
        ];
    }

    public function stressTest(Project $project, $analysis): array
    {
        $prompt = $this->buildStressTestPrompt($project, $analysis);

        return $this->generate($prompt, $this->stressTestSchema());
    }

    private function buildStressTestPrompt(Project $project, $analysis): string
    {
        return <<<PROMPT
        You are an expert startup stress-test analyst.

        Stress-test the following startup idea.

        Project:
        {$project->name}

        Description:
        {$project->description}

        Target audience:
        {$project->target_audience}

        Industry:
        {$project->industry}

        Current analysis summary:
        {$analysis->summary}

        Strengths:
        {$this->formatList($analysis->strengths)}

        Weaknesses:
        {$this->formatList($analysis->weaknesses)}

        Risks:
        {$this->formatList($analysis->risks)}

        Identify:
        - The primary concern that could make the idea fail.
        - Critical questions the founder must answer.
        - Assumptions the idea depends on.
        - Overall risk level.

        Be critical and realistic. Do not simply repeat the analysis.

        PROMPT;
    }

    // make a function that takes an array of items and returns a string with each item on a new line,
    // and each item is prefixed with a dash and a space
    private function formatList($items): string
    {
        return json_encode($items, JSON_PRETTY_PRINT);
    }

    private function stressTestSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'primary_concern' => [
                    'type' => 'string',
                ],
                'critical_questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'assumptions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'risk_level' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high'],
                ],
            ],
            'required' => [
                'primary_concern',
                'critical_questions',
                'assumptions',
                'risk_level',
            ],
        ];
    }

    public function improvement(Project $project, $analysis): array
    {
        $prompt = $this->buildImprovementPrompt($project, $analysis);

        return $this->generate($prompt, $this->improvementSchema());
    }

    private function buildImprovementPrompt(Project $project, $analysis): string
    {
        return <<<PROMPT
        You are an expert startup improvement strategist.

        Based on the project analysis and stress test below,
        identify the most important weaknesses that should be improved.

        Project:
        {$project->name}

        Description:
        {$project->description}

        Target audience:
        {$project->target_audience}

        Industry:
        {$project->industry}

        Analysis summary:
        {$analysis->summary}

        Strengths:
        {$this->formatList($analysis->strengths)}

        Weaknesses:
        {$this->formatList($analysis->weaknesses)}

        Risks:
        {$this->formatList($analysis->risks)}

        Stress test:
        {$this->formatList($analysis->stress_test)}

        For each important weakness:

        - Identify the weakness clearly.
        - Identify the opportunity hidden behind the weakness.
        - Explain why addressing it matters.
        - Suggest one practical action the founder can take.

        Focus on actionable improvements.
        Do not simply repeat the analysis.
        Do not invent market facts that were not provided.

        PROMPT;
    }

    private function improvementSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'improvements' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'weakness' => [
                                'type' => 'string',
                            ],
                            'opportunity' => [
                                'type' => 'string',
                            ],
                            'why_it_matters' => [
                                'type' => 'string',
                            ],
                            'suggested_action' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'weakness',
                            'opportunity',
                            'why_it_matters',
                            'suggested_action',
                        ],
                    ],
                ],
            ],
            'required' => [
                'improvements',
            ],
        ];
    }

    public function pitch(Project $project, $analysis, $appliedImprovements): array
    {
        $prompt = $this->buildPitchPrompt($project, $analysis, $appliedImprovements);

        return $this->generate($prompt, $this->pitchSchema());
    }

    private function buildPitchPrompt(Project $project, $analysis, $appliedImprovements): string
    {
        return <<<PROMPT
        You are a startup pitch writer.
        Create a clear, concise, realistic pitch for the following project.

        PROJECT:
        Name: {$project->name}
        Description: {$project->description}
        Target Audience: {$project->target_audience}
        Industry: {$project->industry}

        ANALYSIS:
        {$analysis->toJson()}

        APPLIED IMPROVEMENTS:
        {$this->formatList($appliedImprovements->toArray())}

        Generate exactly these 8 pitch sections:

        1. problem
        2. solution
        3. target_audience
        4. value_proposition
        5. core_features
        6. business_model
        7. competitive_advantage
        8. go_to_market

        Rules:
        - Generate exactly one section for each section_type.
        - Keep the content concise and presentation-ready.
        - Do not invent facts, statistics, competitors, market sizes, or user numbers.
        - Use only information supported by the project, analysis, and applied improvements.
        - Applied improvements should influence the pitch where relevant.
        - Do not mention that AI generated the pitch.
        - Make the pitch persuasive but realistic.

        PROMPT;
    }

    private function pitchSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'section_type' => [
                                'type' => 'string',
                                'enum' => [
                                    'problem',
                                    'solution',
                                    'target_audience',
                                    'value_proposition',
                                    'core_features',
                                    'business_model',
                                    'competitive_advantage',
                                    'go_to_market',
                                ],
                            ],
                            'content' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'section_type',
                            'content',
                        ],
                    ],
                ],
            ],
            'required' => [
                'sections',
            ],
        ];
    }
}
