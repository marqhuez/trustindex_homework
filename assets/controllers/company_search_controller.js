import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results'];
    static values = { url: String };

    connect() {
        this.debounceTimer = null;
    }

    disconnect() {
        clearTimeout(this.debounceTimer);
    }

    search() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.fetchResults(), 250);
    }

    async fetchResults() {
        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('q', this.inputTarget.value.trim());

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            return;
        }

        this.resultsTarget.innerHTML = await response.text();
        window.history.replaceState(null, '', url.toString());
    }
}
