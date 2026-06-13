/**
 * Reusable debounce utility.
 * Delays the execution of fn until after delay milliseconds have elapsed
 * since the last time the debounced function was invoked.
 */
export function debounce(fn, delay = 300) {
  let timeoutId = null
  
  return function (...args) {
    if (timeoutId) {
      clearTimeout(timeoutId)
    }
    
    timeoutId = setTimeout(() => {
      fn.apply(this, args)
    }, delay)
  }
}
