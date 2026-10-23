import { ref } from 'vue'
import { downloadDocument } from '@/api/tenders'
import { useToasts } from '@/composables/useToasts'
import { saveBlob } from '@/utils/download'

/**
 * Downloads saved documents through the API client (the request carries the access token,
 * so a plain link cannot be used).
 */
export function useDocumentDownload() {
  const { notify } = useToasts()
  /** @type {import('vue').Ref<number|string|null>} id of the document being downloaded */
  const downloadingId = ref(null)

  /**
   * @param {number|string} tenderId
   * @param {{id: number|string, original_name: string}} document
   */
  async function download(tenderId, document) {
    if (downloadingId.value !== null) return
    downloadingId.value = document.id
    try {
      const { blob, filename } = await downloadDocument(tenderId, document)
      saveBlob(blob, filename)
    } catch (e) {
      notify(e.message, 'danger')
    } finally {
      downloadingId.value = null
    }
  }

  return { download, downloadingId }
}
