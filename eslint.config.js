import stylistic from '@stylistic/eslint-plugin';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import prettier from 'eslint-config-prettier/flat';
import importPlugin from 'eslint-plugin-import';
import vue from 'eslint-plugin-vue';

const controlStatements = [
    'if',
    'return',
    'for',
    'while',
    'do',
    'switch',
    'try',
    'throw',
];
const paddingAroundControl = [
    ...controlStatements.flatMap((stmt) => [
        { blankLine: 'always', prev: '*', next: stmt },
        { blankLine: 'always', prev: stmt, next: '*' },
    ]),
];

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        plugins: {
            import: importPlugin,
        },
        settings: {
            'import/resolver': {
                typescript: {
                    alwaysTryTypes: true,
                    project: './tsconfig.json',
                },
                node: true,
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/consistent-type-imports': [
                'error',
                {
                    prefer: 'type-imports',
                    fixStyle: 'separate-type-imports',
                },
            ],
            'import/order': [
                'error',
                {
                    groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
                    alphabetize: { order: 'asc', caseInsensitive: true },
                },
            ],
            'import/consistent-type-specifier-style': [
                'error',
                'prefer-top-level',
            ],
            /*
             * `trans` se importa de `@/lib/i18n`, no del paquete.
             *
             * El paquete devuelve la clave misma cuando falta la traducción, y
             * eso se pinta como texto normal: es exactamente el fallo que
             * sobrevivió a dos fases. El envoltorio de `@/lib/i18n` avisa por
             * consola y lo marca en pantalla durante el desarrollo.
             */
            'no-restricted-imports': [
                'error',
                {
                    paths: [
                        {
                            name: 'laravel-vue-i18n',
                            importNames: ['trans', 'transChoice', 'trans_choice'],
                            message:
                                'Importa `trans` desde `@/lib/i18n`: avisa de las claves sin traducir en desarrollo.',
                        },
                    ],
                },
            ],
        },
    },
    {
        plugins: {
            '@stylistic': stylistic,
        },
        rules: {
            '@stylistic/brace-style': ['error', '1tbs', { allowSingleLine: false }],
            '@stylistic/padding-line-between-statements': [
                'error',
                ...paddingAroundControl,
            ],
        },
    },
    {
        // El envoltorio es justamente quien envuelve al paquete.
        files: ['resources/js/lib/i18n.ts'],
        rules: {
            'no-restricted-imports': 'off',
        },
    },
    {
        ignores: [
            // La comprobación de humo es su propio paquete, con su propio
            // tsconfig y sus propias dependencias: no está en el proyecto de
            // TypeScript de la aplicación y el parser no puede resolverla.
            'tests/humo',
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
            'vite.config.ts',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
    },
    prettier,
    {
        plugins: {
            '@stylistic': stylistic,
        },
        rules: {
            curly: ['error', 'all'],
            '@stylistic/brace-style': ['error', '1tbs', { allowSingleLine: false }],
        },
    },
);
