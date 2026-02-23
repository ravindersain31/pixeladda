document.addEventListener('DOMContentLoaded', () => {
    // ---------------- OTP Input Handling ----------------
    const inputs = document.querySelectorAll<HTMLInputElement>('.otp-box');
    const hidden = document.getElementById('auth_otp_verify_otp') as HTMLInputElement | null;
    const form = document.querySelector<HTMLFormElement>('.otp-form');

    function updateHidden(): void {
        if (!hidden) return;
        hidden.value = Array.from(inputs).map(i => i.value.trim()).join('');
    }

    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHidden();
        });

        input.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Backspace' && input.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    if (form) {
        form.addEventListener('submit', () => updateHidden());
    }

    // ---------------- Resend OTP Logic ----------------
    const resendBtn = document.getElementById('resend-otp-btn') as HTMLButtonElement | null;
    const timerSpan = document.getElementById('resend-timer');
    const csrfInput = document.getElementById('resend-csrf') as HTMLInputElement | null;
    const userIdInput = document.getElementById('resend-user-id') as HTMLInputElement | null;

    if (!resendBtn || !csrfInput || !userIdInput) return;

    const csrfToken = csrfInput.value;
    const userId = userIdInput.value;

    let countdown = 60;
    let timerInterval: number | undefined;

    function updateTimerText(timeLeft: number): void {
        if (!resendBtn) return;
        resendBtn.textContent = `Resend One Time Password in ${timeLeft}s`;
    }

    function startCountdown(): void {
        if (!resendBtn) return;

        resendBtn.disabled = true;
        resendBtn.classList.add('disabled');
        updateTimerText(countdown);

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = window.setInterval(() => {
            countdown--;
            updateTimerText(countdown);

            if (countdown <= 0) {
                clearInterval(timerInterval);
                resendBtn.disabled = false;
                resendBtn.classList.remove('disabled');
                resendBtn.textContent = 'Resend One Time Password.';
                countdown = 60;
            }
        }, 1000);
    }

    resendBtn.addEventListener('click', () => {
        if (resendBtn.disabled) return;

        fetch(`/login-otp/resend/${userId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        })
            .then(async (response) => {
                const data = await response.json();
                if (data.success) {
                    countdown = 60;
                    startCountdown();
                } else {
                    alert(data.message || 'Could not resend One Time Password.');
                }
            })
            .catch((err) => console.error('Error resending One Time Password.:', err));
    });

    startCountdown();
});