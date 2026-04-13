const rawBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').trim()

function normalizeBaseUrl(value) {
  if (!value) return '/api'
  if (/^https?:\/\//i.test(value)) {
    return value.replace(/\/$/, '')
  }
  const withPrefix = value.startsWith('/') ? value : `/${value}`
  return withPrefix.replace(/\/$/, '')
}

const apiBaseCandidates = Array.from(
  new Set([normalizeBaseUrl(rawBaseUrl), '/api']),
)

function joinUrl(baseUrl, path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${baseUrl}${normalizedPath}`
}

function parseMaybeJson(text) {
  if (!text) return null
  try {
    return JSON.parse(text)
  } catch {
    return null
  }
}

export class ApiError extends Error {
  constructor(message, status = 0, payload = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

export function createHttpClient(options = {}) {
  const getToken = options.getToken || (() => null)

  async function request(path, config = {}) {
    let lastError = null

    for (let index = 0; index < apiBaseCandidates.length; index += 1) {
      const baseUrl = apiBaseCandidates[index]
      const token = getToken()
      const headers = {
        Accept: 'application/json',
        ...(config.body ? { 'Content-Type': 'application/json' } : {}),
        ...(config.headers || {}),
      }

      if (token) {
        headers.Authorization = `Bearer ${token}`
      }

      const response = await fetch(joinUrl(baseUrl, path), {
        method: config.method || 'GET',
        headers,
        body: config.body ? JSON.stringify(config.body) : undefined,
      })

      const text = await response.text()
      const payload = parseMaybeJson(text)

      if (response.status === 404 && index < apiBaseCandidates.length - 1) {
        continue
      }

      if (!response.ok) {
        const message = payload?.message || `Erro HTTP ${response.status}`
        lastError = new ApiError(message, response.status, payload)
        break
      }

      return payload
    }

    throw lastError || new ApiError('Falha inesperada na requisição.')
  }

  return {
    get: (path, headers) => request(path, { method: 'GET', headers }),
    post: (path, body, headers) => request(path, { method: 'POST', body, headers }),
    put: (path, body, headers) => request(path, { method: 'PUT', body, headers }),
    patch: (path, body, headers) => request(path, { method: 'PATCH', body, headers }),
    delete: (path, headers) => request(path, { method: 'DELETE', headers }),
  }
}
