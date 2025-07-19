import js from '@eslint/js';
import typescript from '@typescript-eslint/eslint-plugin';
import parser from '@typescript-eslint/parser';

export default [
    js.configs.recommended,
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        languageOptions: {
            parser: parser,
            parserOptions: {
                ecmaVersion: 2021,
                sourceType: 'module',
                ecmaFeatures: {
                    jsx: true
                }
            },
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                URLSearchParams: 'readonly',
                global: 'readonly',
                process: 'readonly',
                HTMLElement: 'readonly',
                HTMLButtonElement: 'readonly',
                HTMLInputElement: 'readonly',
                HTMLTextAreaElement: 'readonly',
                Element: 'readonly',
                URL: 'readonly',
                RequestInit: 'readonly',
                fetch: 'readonly'
            }
        },
        plugins: {
            '@typescript-eslint': typescript
        },
        rules: {
            ...typescript.configs.recommended.rules,
            'complexity': ['error', 20],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/explicit-function-return-type': 'off',
            '@typescript-eslint/no-unused-vars': ['error', { 
                'argsIgnorePattern': '^_',
                'varsIgnorePattern': '^_'
            }]
        }
    },
    {
        files: ['**/__tests__/**/*.{ts,tsx}', '**/*.test.{ts,tsx}'],
        languageOptions: {
            globals: {
                describe: 'readonly',
                it: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                jest: 'readonly',
                test: 'readonly',
                vi: 'readonly'
            }
        },
        rules: {
            'complexity': 'off'
        }
    },
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/**',
            'storage/**',
            'bootstrap/**',
            '*.config.js',
            '*.config.ts'
        ]
    }
];