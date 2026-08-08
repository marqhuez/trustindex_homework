import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['star', 'label'];
    static values = { labels: Array };

    connect() {
        this.paint(this.checkedValue());
    }

    preview(event) {
        this.paint(this.valueOf(event.currentTarget));
    }

    reset() {
        this.paint(this.checkedValue());
    }

    select(event) {
        this.paint(this.valueOf(event.currentTarget));
    }

    valueOf(el) {
        return Number(el.dataset.ratingValue);
    }

    checkedValue() {
        const checked = this.starTargets.find((star) => star.querySelector('input').checked);

        return checked ? this.valueOf(checked) : 0;
    }

    paint(value) {
        this.starTargets.forEach((star) => {
            const filled = this.valueOf(star) <= value;
            star.querySelector('svg').classList.toggle('fill-gold', filled);
            star.querySelector('svg').classList.toggle('text-gold', filled);
            star.querySelector('svg').classList.toggle('text-ink/15', !filled);
        });

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = value > 0
                ? `${value} / 5 — ${this.labelsValue[value - 1]}`
                : 'Tap a star to rate';
        }
    }
}
