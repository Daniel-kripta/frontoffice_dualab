// Rastreadores de IA que se pueden bloquear en SEO → Buscadores de IA.
// Misma lista que config('cms.bots_ia') del backend, que es quien la valida.
export const BOTS_IA = {
  GPTBot: 'OpenAI (entrena ChatGPT)',
  'OAI-SearchBot': 'Búsqueda de ChatGPT',
  'ChatGPT-User': 'ChatGPT al abrir un enlace',
  ClaudeBot: 'Anthropic (Claude)',
  'Claude-SearchBot': 'Búsqueda de Claude',
  PerplexityBot: 'Perplexity',
  'Google-Extended': 'Gemini (no afecta a Google Search)',
  'Applebot-Extended': 'Apple Intelligence',
  'meta-externalagent': 'Meta AI',
  CCBot: 'Common Crawl (datos abiertos para entrenar IA)',
}
