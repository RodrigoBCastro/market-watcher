import { createHttpClient } from './http'

export function createMarketApi(getToken) {
  const http = createHttpClient({ getToken })
  const buildQuery = (params = {}) => {
    const query = new URLSearchParams()

    Object.entries(params).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return
      query.append(key, String(value))
    })

    const serialized = query.toString()
    return serialized ? `?${serialized}` : ''
  }

  return {
    login: (credentials) => http.post('/auth/login', credentials),
    logout: () => http.post('/auth/logout', {}),
    me: () => http.get('/auth/me'),

    getDashboard: () => http.get('/dashboard'),

    getRiskSettings: () => http.get('/risk-settings'),
    updateRiskSettings: (payload) => http.put('/risk-settings', payload),

    calculatePositionSizing: (payload) => http.post('/position-sizing/calculate', payload),

    getPortfolio: () => http.get('/portfolio'),
    getPortfolioOpen: () => http.get('/portfolio/open'),
    getPortfolioClosed: () => http.get('/portfolio/closed'),
    createPortfolioPosition: (payload) => http.post('/portfolio/positions', payload),
    updatePortfolioPosition: (id, payload) => http.patch(`/portfolio/positions/${id}`, payload),
    closePortfolioPosition: (id, payload = {}) => http.post(`/portfolio/positions/${id}/close`, payload),
    partialClosePortfolioPosition: (id, payload = {}) => http.post(`/portfolio/positions/${id}/partial-close`, payload),
    simulatePortfolio: (payload = {}) => http.post('/portfolio/simulate', payload),
    getPortfolioRisk: () => http.get('/portfolio/risk'),
    getPortfolioExposure: () => http.get('/portfolio/exposure'),
    getPortfolioCorrelations: () => http.get('/portfolio/correlations'),

    getPerformanceSummary: (filters = {}) => http.get(`/performance/summary${buildQuery(filters)}`),
    getPerformanceEquityCurve: (filters = {}) => http.get(`/performance/equity-curve${buildQuery(filters)}`),
    getPerformanceBySetup: (filters = {}) => http.get(`/performance/by-setup${buildQuery(filters)}`),
    getPerformanceByAsset: (filters = {}) => http.get(`/performance/by-asset${buildQuery(filters)}`),
    getPerformanceBySector: (filters = {}) => http.get(`/performance/by-sector${buildQuery(filters)}`),
    getPerformanceByRegime: (filters = {}) => http.get(`/performance/by-regime${buildQuery(filters)}`),

    getAlerts: (params = {}) => http.get(`/alerts${buildQuery(params)}`),
    readAlert: (id) => http.post(`/alerts/${id}/read`, {}),

    getAssets: (params = {}) => http.get(`/assets${buildQuery(params)}`),
    createAsset: (payload) => http.post('/assets', payload),
    updateAsset: (id, payload) => http.patch(`/assets/${id}`, payload),
    deleteAsset: (id) => http.delete(`/assets/${id}`),
    updateAssetUniverseMembership: (id, payload) => http.patch(`/assets/${id}/universe-membership`, payload),
    getAssetUniverseStatus: (ticker) => http.get(`/assets/${encodeURIComponent(ticker)}/universe-status`),

    getUniverseSummary: () => http.get('/universes/summary'),
    getUniverseData: (limit = 200) => http.get(`/universes/data?limit=${limit}`),
    getUniverseEligible: (limit = 200) => http.get(`/universes/eligible?limit=${limit}`),
    getUniverseTrading: (limit = 200) => http.get(`/universes/trading?limit=${limit}`),
    recalculateEligibleUniverse: (sync = false) =>
      http.post(sync ? '/universes/recalculate-eligible?sync=1' : '/universes/recalculate-eligible', {}),
    recalculateTradingUniverse: (sync = false) =>
      http.post(sync ? '/universes/recalculate-trading?sync=1' : '/universes/recalculate-trading', {}),

    syncAsset: (ticker) => http.post(`/sync/assets/${encodeURIComponent(ticker)}`, {}),
    syncAssets: () => http.post('/sync/assets', {}),
    syncMarket: () => http.post('/sync/market', {}),
    syncFull: () => http.post('/sync/full', {}),

    getAssetQuotes: (ticker, limit = 120) =>
      http.get(`/assets/${encodeURIComponent(ticker)}/quotes?limit=${limit}`),
    getAssetIndicators: (ticker, limit = 120) =>
      http.get(`/assets/${encodeURIComponent(ticker)}/indicators?limit=${limit}`),
    getAssetAnalysis: (ticker) => http.get(`/assets/${encodeURIComponent(ticker)}/analysis`),

    getAssetMaster: (filters = {}) => http.get(`/asset-master${buildQuery(filters)}`),
    getAssetMasterBySymbol: (symbol) => http.get(`/asset-master/${encodeURIComponent(symbol)}`),
    syncAssetMaster: (sync = false) => http.post(sync ? '/asset-master/sync?sync=1' : '/asset-master/sync', {}),
    getAssetMasterIndexes: (filters = {}) => http.get(`/asset-master/indexes${buildQuery(filters)}`),
    bootstrapDataUniverseFromMaster: (payload = {}, sync = false) =>
      http.post(sync ? '/asset-master/bootstrap-data-universe?sync=1' : '/asset-master/bootstrap-data-universe', payload),
    setAssetMasterBlacklist: (symbol, payload = {}) =>
      http.patch(`/asset-master/${encodeURIComponent(symbol)}/blacklist`, payload),

    getOpportunitiesTop: (date = null) =>
      http.get(date ? `/opportunities/top?date=${date}` : '/opportunities/top'),
    getOpportunitiesAvoid: (date = null) =>
      http.get(date ? `/opportunities/avoid?date=${date}` : '/opportunities/avoid'),

    generateBrief: (date = null) => http.post('/briefs/generate', date ? { date } : {}),
    getBriefs: () => http.get('/briefs'),
    getBriefByDate: (date) => http.get(`/briefs/${date}`),

    getCalls: (status = null, limit = 100) =>
      http.get(status ? `/calls?status=${encodeURIComponent(status)}&limit=${limit}` : `/calls?limit=${limit}`),
    getCallQueue: (limit = 100) => http.get(`/calls/queue?limit=${limit}`),
    getCallById: (id) => http.get(`/calls/${id}`),
    getCallOutcomes: (limit = 100) => http.get(`/calls/outcomes?limit=${limit}`),
    generateCalls: (sync = false) => http.post(sync ? '/calls/generate?sync=1' : '/calls/generate', {}),
    evaluateOpenTrades: (sync = false) =>
      http.post(sync ? '/calls/evaluate-open?sync=1' : '/calls/evaluate-open', {}),
    approveCall: (id, comments = '') => http.post(`/calls/${id}/approve`, comments ? { comments } : {}),
    rejectCall: (id, comments = '') => http.post(`/calls/${id}/reject`, comments ? { comments } : {}),
    publishCall: (id) => http.post(`/calls/${id}/publish`, {}),

    getQuantDashboard: () => http.get('/quant/dashboard'),
    getQuantSetupMetrics: () => http.get('/quant/setup-metrics'),

    getBacktests: (limit = 30) => http.get(`/backtests?limit=${limit}`),
    runBacktest: (payload = {}) => http.post('/backtests/run', payload),

    getOptimizerCurrent: () => http.get('/optimizer/current'),
    runOptimizer: () => http.post('/optimizer/run', {}),
    applyOptimizerWeights: (payload) => http.post('/optimizer/apply', payload),
  }
}
