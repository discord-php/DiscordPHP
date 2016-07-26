<?php

$header = <<<'EOF'
This file is apart of the DiscordPHP project.

Copyright (c) 2016 David Cole <david@team-reflex.com>

This source file is subject to the MIT license that is bundled
with this source code in the LICENSE.md file.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

$fixers = [
    'blank_line_after_namespace',
    'braces',
    'class_definition',
    'elseif',
    'encoding',
    'full_opening_tag',
    'function_declaration',
    'lowercase_constants',
    'lowercase_keywords',
    'method_argument_space',
    'no_closing_tag',
    'no_spaces_after_function_name',
    'no_spaces_inside_parenthesis',
    'no_tab_indentation',
    'no_trailing_whitespace',
    'no_trailing_whitespace_in_comment',
    'psr4',
    'single_blank_line_at_eof',
    'single_class_element_per_statement',
    'single_import_per_statement',
    'single_line_after_imports',
    'switch_case_semicolon_to_colon',
    'switch_case_space',
    'unix_line_endings',
    'visibility_required',
    'blankline_after_open_tag',
    'concat_without_spaces',
    'double_arrow_multiline_whitespaces',
    'duplicate_semicolon',
    'empty_return',
    'extra_empty_lines',
    'include',
    'join_function',
    'list_commas',
    'logical_not_operators_with_successor_space',
    'multiline_array_trailing_comma',
    'multiline_spaces_before_semicolon',
    'namespace_no_leading_whitespace',
    'no_blank_lines_after_class_opening',
    'no_empty_lines_after_phpdocs',
    'object_operator',
    'operators_spaces',
    'phpdoc_indent',
    'phpdoc_inline_tag',
    'phpdoc_no_access',
    'phpdoc_no_package',
    'phpdoc_scalar',
    'phpdoc_short_description',
    'phpdoc_to_comment',
    'phpdoc_trim',
    'phpdoc_type_to_var',
    'phpdoc_var_without_name',
    'phpdoc_align',
    'remove_leading_slash_use',
    'return',
    'self_accessor',
    'short_array_syntax',
    'short_echo_tag',
    'single_array_no_trailing_comma',
    'single_blank_line_before_namespace',
    'single_quote',
    'spaces_before_semicolon',
    'spaces_cast',
    'standardize_not_equal',
    'ternary_spaces',
    'trim_array_spaces',
    'unary_operators_spaces',
    'unused_use',
    'whitespacy_lines',
    'align_double_arrow',
    'align_equals',
    '-phpdoc_no_empty_return',
];

return Symfony\CS\Config\Config::create()
	->fixers($fixers)
	->finder(
		Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__)
	)
;