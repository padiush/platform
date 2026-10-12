import js from '@eslint/js';
import prettierRecommended from 'eslint-plugin-prettier/recommended';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';

export default [
    js.configs.recommended,
    react.configs.flat.recommended,
    react.configs.flat['jsx-runtime'],
    reactHooks.configs.flat.recommended,
    prettierRecommended,
    {
        files: ['resources/js/**/*.{js,jsx,ts,tsx}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: { ...globals.browser, ...globals.node },
        },
        settings: {
            // Stated rather than detected: eslint-plugin-react 7.37 detects the
            // version through an API ESLint 10 removed. It declares support up to
            // ESLint 9.7, so package.json overrides that peer range; both go
            // once the plugin supports ESLint 10.
            react: { version: '19.3' },
        },
        rules: {
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
            'no-undef': 'off',
        },
    },
];
