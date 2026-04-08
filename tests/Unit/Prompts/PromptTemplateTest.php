<?php

declare(strict_types=1);

use NexusScholar\AiChain\Prompts\PromptTemplate;

it('extracts input variables', function () {
    $template = PromptTemplate::from('Hello {name}, your role is {role}.');
    expect($template->inputVariables())->toBe(['name', 'role']);
});

it('formats template successfully with all variables provided', function () {
    $template = PromptTemplate::from('Hello {name}, your role is {role}.');
    $result = $template->format(['name' => 'Alice', 'role' => 'Admin']);
    expect($result)->toBe('Hello Alice, your role is Admin.');
});

it('throws exception when variables are missing', function () {
    $template = PromptTemplate::from('Hello {name}, your role is {role}.');
    expect(fn() => $template->format(['name' => 'Alice']))
        ->toThrow(InvalidArgumentException::class, 'Missing prompt variables: role');
});

it('handles duplicate variables gracefully', function () {
    $template = PromptTemplate::from('{word} is {word}.');
    expect($template->inputVariables())->toBe(['word']);
    
    $result = $template->format(['word' => 'Test']);
    expect($result)->toBe('Test is Test.');
});

it('ignores braces that do not match the variable pattern', function () {
    $template = PromptTemplate::from('function foo() { return "{value}"; }');
    expect($template->inputVariables())->toBe(['value']);
    
    $result = $template->format(['value' => 'bar']);
    expect($result)->toBe('function foo() { return "bar"; }');
});
