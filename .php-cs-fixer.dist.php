<?php

$header = <<<'EOF'
This file is a part of the DiscordPHP project.

Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>

This file is subject to the MIT license that is bundled
with this source code in the LICENSE.md file.
EOF;

$fixers = [
    'blank_line_after_namespace',
    'braces',
    'class_definition',
    'elseif',
    'encoding',
    'full_opening_tag',
    'function_declaration',
    'lowercase_keywords',
    'method_argument_space',
    'no_closing_tag',
    'no_spaces_after_function_name',
    'no_spaces_inside_parenthesis',
    'no_trailing_whitespace',
    'no_trailing_whitespace_in_comment',
    'single_blank_line_at_eof',
    'single_class_element_per_statement',
    'single_import_per_statement',
    'single_line_after_imports',
    'switch_case_semicolon_to_colon',
    'switch_case_space',
    'visibility_required',
    'blank_line_after_opening_tag',
    'no_multiline_whitespace_around_double_arrow',
    'no_empty_statement',
    'include',
    'no_trailing_comma_in_list_call',
    'not_operator_with_successor_space',
    'no_leading_namespace_whitespace',
    'no_blank_lines_after_class_opening',
    'no_blank_lines_after_phpdoc',
    'object_operator_without_whitespace',
    'binary_operator_spaces',
    'phpdoc_indent',
    'general_phpdoc_tag_rename',
    'phpdoc_inline_tag_normalizer',
    'phpdoc_tag_type',
    'phpdoc_no_access',
    'phpdoc_no_package',
    'phpdoc_scalar',
    'phpdoc_summary',
    'phpdoc_to_comment',
    'phpdoc_trim',
    'phpdoc_var_without_name',
    'no_leading_import_slash',
    'no_trailing_comma_in_singleline_array',
    'single_blank_line_before_namespace',
    'single_quote',
    'no_singleline_whitespace_before_semicolons',
    'cast_spaces',
    'standardize_not_equals',
    'ternary_operator_spaces',
    'trim_array_spaces',
    'unary_operator_spaces',
    'no_unused_imports',
    'no_useless_else',
    'no_useless_return',
    'phpdoc_no_empty_return',
    'no_extra_blank_lines',
    'multiline_whitespace_before_semicolons',
];

$rules = [
    'concat_space' => ['spacing' => 'none'],
    'phpdoc_no_alias_tag' => ['replacements' => ['type' => 'var']],
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => ['align_double_arrow' => true, 'align_equals' => true],
    'header_comment' => ['header' => $header],
    'indentation_type' => true,
    'phpdoc_align' => [
        'align' => 'vertical',
        'tags' => ['param', 'property', 'property-read', 'property-write', 'return', 'throws', 'type', 'var', 'method'],
    ],
    'blank_line_before_statement' => ['statements' => ['return']],
    'constant_case' => ['case' => 'lower'],
    'echo_tag_syntax' => ['format' => 'long'],
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],
];

foreach ($fixers as $fix) {
    $rules[$fix] = true;
}

$config = new PhpCsFixer\Config();

return $config
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
    );
