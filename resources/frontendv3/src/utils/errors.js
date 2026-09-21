/**
 * Pulls a human-readable message out of an axios error, regardless of whether
 * it's a validation failure, a backend-thrown message, or the request never
 * reaching the server at all (network down, backend not running, CORS, timeout).
 */
export function extractErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
  if (!error?.response) {
    return 'Could not reach the server. Check your connection and try again.'
  }

  const data = error.response.data

  if (data?.message) return data.message

  if (data?.errors) {
    const firstField = Object.values(data.errors)[0]
    if (Array.isArray(firstField) && firstField.length) return firstField[0]
  }

  return fallback
}

/** Per-field validation errors from a 422 response, keyed the same as the form fields. */
export function extractFieldErrors(error) {
  return error?.response?.data?.errors || {}
}
