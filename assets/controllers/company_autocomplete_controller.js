import { Controller } from '@hotwired/stimulus';
import debounce from '../utils/debounce.js';

export default class extends Controller {
    static targets = ['input', 'results', 'option'];
    static values = { url: String };

    connect() {
        this.activeIndex = -1;
        this.abortController = null;
        this.boundHandleOutsideClick = this.handleOutsideClick.bind(this);
        document.addEventListener('click', this.boundHandleOutsideClick);
        this.debouncedFetch = debounce((query) => this.fetchResults(query), 200);
    }

    disconnect() {
        document.removeEventListener('click', this.boundHandleOutsideClick);
        this.debouncedFetch.cancel();
        this.abortController?.abort();
    }

    handleOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    search() {
        const query = this.inputTarget.value.trim();

        if (query.length < 1) {
            this.debouncedFetch.cancel();
            this.close();
            return;
        }

        this.debouncedFetch(query);
    }

    async fetchResults(query) {
        this.abortController?.abort();
        this.abortController = new AbortController();

        let response;
        try {
            response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                signal: this.abortController.signal,
            });
        } catch {
            return; // aborted by a newer keystroke
        }

        if (!response.ok) {
            this.close();
            return;
        }

        this.renderResults(await response.json());
    }

    renderResults(companies) {
        this.activeIndex = -1;
        this.resultsTarget.innerHTML = '';

        if (companies.length === 0) {
            this.close();
            return;
        }

        companies.forEach((company) => {
            const option = document.createElement('li');
            option.setAttribute('role', 'option');
            option.setAttribute('data-company-autocomplete-target', 'option');
            option.setAttribute('data-action', 'click->company-autocomplete#select');
            option.dataset.name = company.name;
            option.className = 'cursor-pointer px-4 py-2 text-sm text-ink hover:bg-paper';
            option.textContent = company.name;
            this.resultsTarget.appendChild(option);
        });

        this.open();
    }

    select(event) {
        this.inputTarget.value = event.currentTarget.dataset.name;
        this.close();
        this.inputTarget.focus();
    }

    keydown(event) {
        if (this.resultsTarget.classList.contains('hidden')) {
            return;
        }

        const options = this.optionTargets;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.activeIndex = Math.min(this.activeIndex + 1, options.length - 1);
            this.highlight(options);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.activeIndex = Math.max(this.activeIndex - 1, 0);
            this.highlight(options);
        } else if (event.key === 'Enter') {
            if (this.activeIndex >= 0 && options[this.activeIndex]) {
                event.preventDefault();
                this.inputTarget.value = options[this.activeIndex].dataset.name;
                this.close();
            }
        } else if (event.key === 'Escape') {
            this.close();
        }
    }

    highlight(options) {
        options.forEach((option, index) => {
            option.classList.toggle('bg-paper', index === this.activeIndex);
        });
    }

    open() {
        this.inputTarget.setAttribute('aria-expanded', 'true');
        this.resultsTarget.classList.remove('hidden');
    }

    close() {
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this.resultsTarget.classList.add('hidden');
        this.activeIndex = -1;
    }
}
