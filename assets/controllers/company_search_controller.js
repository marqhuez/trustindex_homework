import { Controller } from '@hotwired/stimulus';
import debounce from '../utils/debounce.js';

export default class extends Controller {
    static targets = ['input', 'results'];
    static values = { url: String };

    connect() {
        this.abortController = null;
        this.debouncedSearch = debounce(() => this.fetchResults(), 250);
    }

    disconnect() {
        this.debouncedSearch.cancel();
        this.abortController?.abort();
    }

    search() {
        this.debouncedSearch();
    }

    async fetchResults() {
        this.abortController?.abort();
        this.abortController = new AbortController();

        const query = this.inputTarget.value.trim();
        const url = `${this.urlValue}?q=${encodeURIComponent(query)}`;

        let response;
        try {
            response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.abortController.signal,
            });
        } catch {
            return; // aborted by a newer keystroke
        }

        if (!response.ok) {
            return;
        }

        this.resultsTarget.innerHTML = await response.text();
        window.history.replaceState(null, '', url);
    }
}
