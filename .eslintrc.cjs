module.exports = {
  env: {
    node: true,
    browser: true,
  },
  extends: [
    'eslint:recommended',
    'plugin:vue/vue3-recommended',
    'prettier'
  ],
  rules: {
    // [OPTIMASI CODE QUALITY]: Aturan resmi untuk memastikan kode bebas bug dan ringan
    'vue/no-unused-vars': 'warn',
    'no-unused-vars': 'warn',
    'vue/multi-word-component-names': 'off', // Dimatikan agar tidak merepotkan penamaan komponen
    'vue/no-mutating-props': 'error', // Cegah bug mutasi properti Vue
    'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
  }
}
