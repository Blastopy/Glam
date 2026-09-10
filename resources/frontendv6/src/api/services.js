import api from './client'

/**
 * Builds a FormData payload, skipping null/undefined so partial updates
 * (e.g. no new image on this edit) don't overwrite existing values.
 */
function toFormData(payload) {
  const formData = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (value === null || value === undefined) return
    formData.append(key, value)
  })
  return formData
}

export default {
  create(payload) {
    // multipart whenever an image file is present, JSON otherwise — either works,
    // but multipart is simplest to always use here since the image is optional per-request
    return api.post('/services', toFormData(payload), {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  update(id, payload) {
    // Laravel resolves POST + _method=PATCH as a PATCH request — used here because
    // native PATCH requests can't carry multipart file uploads reliably from the browser.
    const formData = toFormData(payload)
    formData.append('_method', 'PATCH')
    return api.post(`/services/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  remove(id) {
    return api.delete(`/services/${id}`)
  },
}
