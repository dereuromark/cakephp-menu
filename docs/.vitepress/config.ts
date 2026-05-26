import { type HeadConfig } from 'vitepress'
import { withMermaid } from 'vitepress-plugin-mermaid'

const siteUrl = 'https://dereuromark.github.io/cakephp-menu/'
const siteDescription =
  'Composable menu builder, active-state matcher, and renderer for CakePHP 5.3+ — dependency free.'
const ogImage = siteUrl + 'og-image.png'

function sidebar() {
  return [
    {
      text: 'Guide',
      collapsed: false,
      items: [
        { text: 'Getting Started', link: '/guide/' },
        { text: 'Concepts', link: '/guide/concepts' },
        { text: 'Building Menus', link: '/guide/building' },
        { text: 'Resolvers & Active State', link: '/guide/resolvers' },
        { text: 'Rendering', link: '/guide/rendering' },
        { text: 'Renderer Gallery', link: '/guide/gallery' },
        { text: 'Recipes', link: '/guide/recipes' },
        { text: 'Extending', link: '/guide/extending' },
        { text: 'FAQ & Troubleshooting', link: '/guide/faq' },
      ],
    },
    {
      text: 'Reference',
      collapsed: false,
      items: [
        { text: 'Renderer Options', link: '/reference/renderer-options' },
        { text: 'Item Options', link: '/reference/item-options' },
        { text: 'Helper & Render Options', link: '/reference/helper-options' },
        { text: 'API Cheat Sheet', link: '/reference/cheatsheet' },
      ],
    },
  ]
}

export default withMermaid({
  title: 'cakephp-menu',
  description: siteDescription,
  base: '/cakephp-menu/',
  lastUpdated: true,
  sitemap: {
    hostname: siteUrl,
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-menu/favicon.svg', type: 'image/svg+xml' }],
    ['meta', { name: 'theme-color', content: '#c4313a' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:site_name', content: 'cakephp-menu' }],
    ['meta', { property: 'og:image', content: ogImage }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:image', content: ogImage }],
  ],
  transformHead: ({ pageData }) => {
    const head: HeadConfig[] = []
    const pageTitle = pageData.frontmatter.title ?? pageData.title
    const fullTitle = pageTitle ? `${pageTitle} | cakephp-menu` : 'cakephp-menu'
    const description =
      pageData.frontmatter.description ?? pageData.description ?? siteDescription
    const url = siteUrl + pageData.relativePath.replace(/(index)?\.md$/, '')

    head.push(['meta', { property: 'og:title', content: fullTitle }])
    head.push(['meta', { property: 'og:description', content: description }])
    head.push(['meta', { property: 'og:url', content: url }])
    head.push(['meta', { name: 'twitter:title', content: fullTitle }])
    head.push(['meta', { name: 'twitter:description', content: description }])

    return head
  },
  themeConfig: {
    logo: '/logo.svg',
    outline: [2, 3],
    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
      { text: 'Gallery', link: '/guide/gallery', activeMatch: '/guide/gallery' },
      { text: 'Reference', link: '/reference/renderer-options', activeMatch: '/reference/' },
      {
        text: 'Links',
        items: [
          { text: 'Live Sandbox Demo', link: 'https://sandbox.dereuromark.de/menu-sandbox' },
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-menu' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-menu' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-menu/issues' },
        ],
      },
    ],
    sidebar: {
      '/': sidebar(),
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-menu' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-menu/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})
