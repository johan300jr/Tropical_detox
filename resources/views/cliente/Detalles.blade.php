<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle</title>
    {{--
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"> --}}

    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Site Icons -->
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <!-- Site CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="/css/responsive.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">
</head>

<body>
    @include('cliente.nav')

    <div class="container">
        <div class="row" style="margin-top:1.5em ">
            <div class="col-lg-12">
                <div class="title-all text-center">
                    <h1>pedido</h1>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <section class="section">

                    <div class="section-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="container">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h1>Detalles del pedido</h1>
                                                    {{-- <p><strong>Usuario:</strong> {{ $pedido->user->name }}</p> --}}
                                                    @if (!empty($pedido->Telefono))
                                                        <p><strong>Teléfono:</strong> {{ $pedido->Telefono }}</p>
                                                    @endif
                                                    <p><strong>Estado:</strong> {{ $pedido->Estado }}</p>
                                                    <p><strong>Fecha:</strong> {{ $pedido->Fecha }}</p>
                                                    <p><strong>Hora:</strong> {{ substr($pedido->created_at, 11, 5) }}</p>

                                                    <p><strong>Total:</strong>
                                                        {{ number_format($pedido->Total, 0, ',', '.') }}</p>
                                                    @if ($pedido->Direcion)
                                                        <p><strong>direccion:</strong>
                                                            {{ $pedido->Direcion }}</p>
                                                    @else
                                                        <p><strong>direccion:</strong>
                                                            {{ $pedido->users->direccion }}</p>
                                                    @endif
                                                    <h2>Productos</h2>
                                                    @if (!empty($pedido->Nombre))
                                                        <p><strong>Descripción:</strong> {{ $pedido->Nombre }}</p>
                                                    @endif
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th>Producto</th>
                                                                <th>Cantidad</th>
                                                                <th>Precio unitario</th>
                                                                {{-- <th>Detalles</th> --}}
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($detalles_pedidos as $detalle)
                                                                <tr>
                                                                    <td>{{ $detalle->Nombre }}</td>
                                                                    <td>{{ $detalle->cantidad }}</td>
                                                                    <td>{{ number_format($detalle->precio_unitario, 0, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                            <?php $per = ''; ?>
                                                            @foreach ($personaliza as $personalizas)
                                                                @if (!($personalizas->nombre == $per))
                                                                    <?php $per = $personalizas->nombre; ?>
                                                                    <?php $lastSubtotal = null; ?>
                                                                    <!-- Add this line to initialize the variable -->
                                                                    @foreach ($personaliza as $personalizaInner)
                                                                        <!-- Loop through the personaliza array again to find the last Subtotal for the current $per -->
                                                                        @if ($personalizaInner->nombre == $per)
                                                                            <?php $lastSubtotal = $personalizaInner->Subtotal; ?>
                                                                        @endif
                                                                    @endforeach
                                                                    <tr>
                                                                        <td>{{ $personalizas->nombre }}</td>
                                                                        <td>{{ $personalizas->cantidad }}</td>
                                                                        <td> {{ number_format($lastSubtotal, 0, ',', '.') }}
                                                                        </td>
                                                                        <!-- Print the last Subtotal for the current $per -->
                                                                        <!-- ... -->
                                                                        <td>
                                                                            <button
                                                                                class="btn btn-info btn-sm float-right"
                                                                                data-toggle="modal"
                                                                                data-target="#productModal_{{ $personalizas->insumos }}"
                                                                                data-details="{{ json_encode($personalizas) }}">Detalles
                                                                            </button>
                                                                        </td>
                                                                        <!-- ... -->
                                                                    </tr>
                                                                @endif
                                                            @endforeach

                                                         
                                                            @foreach ($personaliza as $personalizas)
                                                                <div class="modal fade my-modal"
                                                                    id="productModal_{{ $personalizas->insumos }}"
                                                                    tabindex="-1" role="dialog"
                                                                    aria-labelledby="productModalLabel_{{ $personalizas->insumos }}"
                                                                    aria-hidden="true">
                                                                    <div class="modal-dialog" role="document">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title"
                                                                                    id="productModalLabel_{{ $personalizas->insumos }}">
                                                                                    Detalles del producto</h5>
                                                                                <button type="button" class="close"
                                                                                    data-dismiss="modal"
                                                                                    aria-label="Close">
                                                                                    <span
                                                                                        aria-hidden="true">&times;</span>
                                                                                </button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <h6 class="Nombre">Nombre:
                                                                                </h6>
                                                                                <div
                                                                                    id="productModalIngredients_{{ $personalizas->insumos }}">
                                                                                    <!-- Ingredientes se agregarán aquí -->
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button"
                                                                                    class="btn btn-secondary"
                                                                                    data-dismiss="modal">Cerrar</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- JS para cargar los ingredientes en el modal -->
                                                                <script>
                                                                    $(document).ready(function() {
                                                                        $('#productModal_{{ $personalizas->insumos }}').on('show.bs.modal', function(event) {
                                                                            var button = $(event.relatedTarget);
                                                                            var details = button.data('details');
                                                                            var modal = $(this);

                                                                            modal.find('.modal-title').text('Detalles del producto: ' + details.nombre);
                                                                            modal.find('.Nombre').text('Nombre: ' + details.nombre);

                                                                            var ingredientList = '<ul>';
                                                                            @foreach ($personaliza as $q)
                                                                                if ("{{ $q->nombre }}" == details.nombre) {
                                                                                    ingredientList +=
                                                                                        '<li>Insumo: {{ $q->insumos }} - {{ optional(App\Models\Insumo::find($q->insumos))->nombre ?? 'Nombre no encontrado' }}</li>';
                                                                                }
                                                                            @endforeach
                                                                            ingredientList += '</ul>';
                                                                            modal.find('#productModalIngredients_{{ $personalizas->insumos }}').html(ingredientList);
                                                                        });
                                                                    });
                                                                </script>
                                                            @endforeach







                                                        </tbody>
                                                    </table>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <!-- JS -->




    <!-- JS -->

    @include('cliente.footer')



    <!-- ALL JS FILES -->
    <script src="/js/jquery-3.2.1.min.js"></script>
    <script src="/js/popper.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <!-- ALL PLUGINS -->
    <script src="/js/jquery.superslides.min.js"></script>
    <script src="/js/bootstrap-select.js"></script>
    <script src="/js/inewsticker.js"></script>
    <script src="/js/bootsnav.js."></script>
    <script src="/js/images-loded.min.js"></script>
    <script src="/js/isotope.min.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/baguetteBox.min.js"></script>
    <script src="/js/form-validator.min.js"></script>
    <script src="/js/contact-form-script.js"></script>
    <script src="/js/custom.js"></script>
</body>

</html>
