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
/*
 * Excepciones de `vue/no-bare-strings-in-template`.
 *
 * La opción `allowlist` **sustituye** a la lista por defecto del plugin, así
 * que hay que repetirla entera; y sus entradas se escapan antes de compilar el
 * patrón, así que son cadenas literales, no expresiones regulares.
 *
 * Funciona quitando del texto todo lo que aparece aquí y avisando si queda
 * algo. Por eso admitir los dígitos no debilita la regla: «100% seguro» sigue
 * dejando «% seguro» y sigue avisando. Lo único que pasa a estar permitido es
 * un texto **entero** de puntuación y números, que nunca hay que traducir.
 *
 * Para un caso suelto que no sea de estas clases, la salida es un comentario
 * en el propio archivo diciendo por qué:
 *
 *     <!-- eslint-disable-next-line vue/no-bare-strings-in-template
 *          (motivo concreto) -->
 *
 * Nunca desactivando la regla entera.
 */
const puntuacionPermitida = [
    // La lista por defecto del plugin, tal cual.
    '(', ')', ',', '.', '&', '+', '-', '=', '*', '/', '#', '%', '!', '?',
    ':', '[', ']', '{', '}', '<', '>', '|',
    '\u00B7', '\u2022', '\u2010', '\u2013', '\u2014', '\u2212',
    // Añadidos del proyecto:
    // Los dígitos, para que un valor puramente numérico no pida traducción:
    // los tamaños de página del selector de la tabla y el «0» de un contador.
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    // La flecha del historial de estados. Es un símbolo, no una palabra:
    // «pendiente → en proceso» se lee igual en los dos idiomas.
    '\u2192',
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
        /*
         * Ningún texto visible se escribe en la plantilla.
         *
         * Es la otra mitad de la red de i18n, y detecta lo contrario que
         * `CoberturaDeClavesTest`: aquella caza claves que no resuelven —que se
         * pintan como su ruta—, y esta caza texto que **nunca pasó por el
         * sistema de traducción**. Un `Email address` incrustado se renderiza
         * perfectamente y ninguna comprobación de humo lo ve; simplemente está
         * en el idioma equivocado.
         *
         * Cubre el texto de la plantilla y los atributos que se muestran. **No
         * cubre** los literales del `<script>` ni los que se pasan como prop a
         * un componente: para esos, la costumbre del proyecto es que el valor
         * sea una clave y quien la pinta la resuelva con `$t`, como hacen
         * `NavMain`, `Breadcrumbs` y `AuthSimpleLayout`.
         */
        files: ['resources/js/**/*.vue'],
        rules: {
            'vue/no-bare-strings-in-template': [
                'error',
                {
                    allowlist: puntuacionPermitida,
                    // `placeholder` en cualquier etiqueta, no solo en `input`:
                    // los campos del proyecto son componentes propios.
                    attributes: {
                        '/.+/': [
                            'title',
                            'alt',
                            'placeholder',
                            'aria-label',
                            'aria-placeholder',
                            'aria-roledescription',
                            'aria-valuetext',
                        ],
                    },
                    directives: ['v-text'],
                },
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
