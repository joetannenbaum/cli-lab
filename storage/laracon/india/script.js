JSON.stringify(
    [...document.querySelectorAll('.speakers-box')].map((el) => ({
        name: el.querySelector('.border-animation').innerText,
        title: el.querySelector('.speaker-detail').innerText,
        social: el.querySelector('a').href,
    })),
);
