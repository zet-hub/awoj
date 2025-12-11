async function postForm(url, form) {
    const res = await fetch(url, {
        method: 'POST',
        body: new FormData(form),
    });
    const data = await res.json().catch(() => ({}));
    if (data.redirect) {
        window.location = data.redirect;
        return;
    }
    if (!res.ok) {
        throw new Error(data.error || 'Request failed');
    }
    return data;
}

async function getJSON(url) {
    const res = await fetch(url);
    const data = await res.json().catch(() => ({}));
    if (data.redirect) {
        window.location = data.redirect;
        return null;
    }
    if (!res.ok) {
        throw new Error(data.error || 'Request failed');
    }
    return data;
}

