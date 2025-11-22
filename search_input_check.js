document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('table');
    const responseDiv = document.getElementById('response');

    input.addEventListener('input', () => {
        const value = input.value;
        sendDataToPHP(value);
});

    async function sendDataToPHP(value) {
        try {
            const response = await fetch('search_func.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ table: value })
            });
            const data = await response.text();
            responseDiv.innerHTML = data;
        } catch (error) {
            console.error('Error:', error);
            responseDiv.textContent = 'An error occurred while processing your request.';
        }
    }
});