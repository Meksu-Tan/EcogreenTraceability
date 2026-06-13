const localStorageMock = (() => {
  let store = {}
  return {
    getItem:    key      => store[key] ?? null,
    setItem:    (key, v) => { store[key] = String(v) },
    removeItem: key      => { delete store[key] },
    clear:      ()       => { store = {} },
  }
})()
Object.defineProperty(window, 'localStorage', { value: localStorageMock })
