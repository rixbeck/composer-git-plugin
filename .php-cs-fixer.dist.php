<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
                   '@Symfony' => true,
                   'yoda_style' => false,
                   'concat_space' => true,
                   'cast_spaces' => false,
                   'array_syntax' => ['syntax' => 'short'],
                   'single_line_comment_style' => true,
                   'line_ending' => true,
                   'binary_operator_spaces' => true,
                   'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
                   'single_line_throw' => false,
                   'ordered_class_elements' => [
                        'order' => [
                            'property_public',
                            'property_protected',
                            'property_private',
                            'method_public',
                            'method_protected',
                            'method_private',
                        ],
                        'sort_algorithm' => 'none',
                    ],
               ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache') // forward compatibility with 3.x line
    ;
