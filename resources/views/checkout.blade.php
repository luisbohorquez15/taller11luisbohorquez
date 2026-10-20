<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout de Prueba - Stripe</title>
    <!-- Tailwind CSS CDN para estilos rápidos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Stripe.js SDK CDN -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Checkout de Prueba</h2>

        <!-- Formulario de Configuración del Pago -->
        <form id="payment-form">
            <div class="mb-4">
                <label for="amount" class="block text-gray-700 font-medium mb-2">Monto a pagar</label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-md">$</span>
                    <input type="number" id="amount" name="amount" step="0.01" min="1" value="10.00" class="rounded-none rounded-r-md bg-gray-50 border border-gray-300 text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm p-2.5" required>
                </div>
            </div>

            <div class="mb-6">
                <label for="currency" class="block text-gray-700 font-medium mb-2">Moneda</label>
                <select id="currency" name="currency" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="usd">USD - Dólares estadounidenses</option>
                    <option value="eur">EUR - Euros</option>
                </select>
            </div>

            <button type="button" id="init-payment-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200 mb-4">
                Generar Formulario de Pago
            </button>

            <!-- Contenedor donde Stripe renderiza el formulario seguro de tarjeta -->
            <div id="payment-element-container" class="hidden my-4">
                <div id="payment-element" class="mb-4"></div>

                <button type="submit" id="submit-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded transition duration-200">
                    Pagar Ahora
                </button>
            </div>

            <div id="payment-message" class="hidden mt-4 p-3 rounded text-sm text-center"></div>
        </form>
    </div>

    <script>
        // Inicializar Stripe con la clave pública de Blade
        const stripe = Stripe("{{ config('services.stripe.key') }}");
        let elements;

        const initBtn = document.getElementById('init-payment-btn');
        const paymentElementContainer = document.getElementById('payment-element-container');
        const paymentForm = document.getElementById('payment-form');
        const paymentMessage = document.getElementById('payment-message');
        const submitBtn = document.getElementById('submit-btn');

        // Paso 1: Pedir al backend que cree el PaymentIntent y cargue Stripe Elements
        initBtn.addEventListener('click', async () => {
            const amount = document.getElementById('amount').value;
            const currency = document.getElementById('currency').value;

            if (!amount || amount <= 0) {
                alert('Por favor ingrese un monto válido.');
                return;
            }

            initBtn.disabled = true;
            initBtn.innerText = 'Cargando...';

            try {
                const response = await fetch('/create-payment-intent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ amount, currency })
                });

                const data = await response.json();

                if (data.error) {
                    showMessage(data.error, true);
                    initBtn.disabled = false;
                    initBtn.innerText = 'Generar Formulario de Pago';
                    return;
                }

                // Inicializar Stripe Elements con el clientSecret obtenido
                elements = stripe.elements({ clientSecret: data.clientSecret });
                const paymentElement = elements.create('payment');
                paymentElement.mount('#payment-element');

                // Mostrar el componente de pago de Stripe
                paymentElementContainer.classList.remove('hidden');
                initBtn.classList.add('hidden');

            } catch (err) {
                showMessage('Error de conexión con el servidor', true);
                initBtn.disabled = false;
                initBtn.innerText = 'Generar Formulario de Pago';
            }
        });

        // Paso 2: Confirmar el pago directamente hacia los servidores de Stripe
        paymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.innerText = 'Procesando...';

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.href, // Redirección tras pago exitoso
                },
                redirect: 'if_required' // Permite manejar la confirmación inline sin recargar la página
            });

            if (error) {
                showMessage(error.message, true);
                submitBtn.disabled = false;
                submitBtn.innerText = 'Pagar Ahora';
            } else {
                showMessage('¡Pago procesado con éxito!', false);
                paymentElementContainer.classList.add('hidden');
            }
        });

        function showMessage(messageText, isError = false) {
            paymentMessage.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
            if (isError) {
                paymentMessage.classList.add('bg-red-100', 'text-red-700');
            } else {
                paymentMessage.classList.add('bg-green-100', 'text-green-700');
            }
            paymentMessage.innerText = messageText;
        }
    </script>
</body>
</html>