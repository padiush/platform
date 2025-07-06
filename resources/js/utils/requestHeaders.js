function requestHeaders() {
    return {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

export { requestHeaders };
