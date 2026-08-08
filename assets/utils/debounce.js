export default function debounce(fn, delay) {
    let timer = null;

    const debounced = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };

    debounced.cancel = () => clearTimeout(timer);

    return debounced;
}
