<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\detalle_pedidos;
use App\Models\InsumoProducto;
use App\Models\producPerz;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use App\Models\pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;

class pedidoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:pedidos', ['only' => ['create', 'store', 'destroy', 'edit', 'update', 'index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Cargamos todos los pedidos y sus respectivos usuarios utilizando la función "with()"
        $pedidos = Pedido::with('users')->get();
        // Retornamos la vista "index" y le pasamos la variable "pedidos"
        $producto = Producto::all();
        // return view('pedidos.index', ["pedidos"=>$pedidos,"producto"=>$producto]);
        return view('pedidos.index', compact('pedidos'));
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Retornamos la vista "create" y le pasamos las variables "productos" y "users"
        return view('pedidos.create', ['productos' => Producto::all(), 'users' => User::all(), 'Insumo' => Insumo::all()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $productos = $request->input('ProductoID');
        $cantidades = $request->input('Cantidad');
        $personalizadosArray = json_decode($request->input('personalizadosArray'), true);
        if ($productos) {
            $request->validate([
                'Nombre' => 'nullable|max:500',
                'Usuario' => 'required',
                'ProductoID' => 'required|array',
                'ProductoID.*' => 'integer',
                'Total' => 'required|numeric',
            ]);
        }
        if ($personalizadosArray) {
            $request->validate([
                'Nombre' => 'nullable|max:500',
                'Usuario' => 'required',
                'Total' => 'required|numeric',
            ]);
        } else {
            $request->validate([
                'Nombre' => 'nullable|max:500',
                'Usuario' => 'required',
                'ProductoID' => 'required|array',
                'ProductoID.*' => 'integer',
                'Total' => 'required|numeric',
            ]);
        }

        $pedido = new Pedido();
        $pedido->Nombre = $request->input('Nombre');
        $pedido->Estado = "En_proceso";
        $pedido->Fecha = now();
        $pedido->id_users = $request->input('Usuario');
        $pedido->Total = $request->input('Total');
        $pedido->save();
        $total = 0;
        if (!empty($personalizadosArray)) {
            foreach ($personalizadosArray as $personalizado) {
                // Guardar los datos del personalizado en la base de datos
                $insumos = $personalizado['insumos'];
                $Nombre = $personalizado['Nombre'];
                $subtotal = $personalizado['Subtotal'];
                $id = '';
                $Nombres = '';
                $total += $subtotal;
                foreach ($insumos as $insumo) {
                    $insumoData = explode(':', $insumo);
                    $NombreData = explode(':', $Nombre);
                    $subtotalData = explode(':', $subtotal);
                    $id = trim($insumoData[0]);
                    $Nombres = trim($NombreData[0]);
                    $subtotal = trim($subtotalData[0]);
                    $personalizadoModel = new producPerz();
                    $personalizadoModel->nombre = $Nombres;
                    $personalizadoModel->cantidad = 1;
                    $personalizadoModel->Subtotal = $subtotal;
                    $personalizadoModel->id_pedidos = $pedido->id;
                    $personalizadoModel->insumos = $id;
                    $personalizadoModel->save();
                    // Realizar el descuento del insumo asociado al producto personalizado
                    $insumo = Insumo::find($id);
                    if ($insumo) {
                        $insumo->cantidad_disponible -= 1;
                        if ($insumo->cantidad_disponible < 3) {
                            $pedidos = Pedido::all();
                            return redirect()->back()->withErrors(['error' => 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre]);
                            // return view('pedidos.index', compact('pedidos'))->with('error', 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre);
                        }
                        $insumo->save();
                    }
                }
            }
        }
        if (!empty($productos)) {
            foreach ($productos as $index => $producto_id) {
                $producto = Producto::find($producto_id);
                $detalles_pedidos = new detalle_pedidos();
                $detalles_pedidos->id_pedidos = $pedido->id;
                $detalles_pedidos->cantidad = $cantidades[$index];
                $detalles_pedidos->precio_unitario = $producto->precio;
                $detalles_pedidos->id_productos = $producto_id;
                $detalles_pedidos->Nombre = $producto->nombre;
                $subtotal = $detalles_pedidos->cantidad * $detalles_pedidos->precio_unitario;
                $total += $subtotal;
                $insumo_producto = InsumoProducto::where('producto_id', $producto_id)->first();
                for ($i = 0; $i < 3; $i++) {
                    if ($insumo_producto) {
                        $insumo = Insumo::find($insumo_producto->insumo_id);
                        if ($insumo) {
                            $insumo->cantidad_disponible -= 3;
                            if ($insumo->cantidad_disponible < 3) {
                                $pedidos = Pedido::all();
                                return redirect()->back()->withErrors(['error' => 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre]);
                                // return view('pedidos.index', compact('pedidos'))->with('error', 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre);
                            }
                            $insumo->save();
                        }
                    }
                }
                $detalles_pedidos->save();
            }
        }
        $pedido->Total = $total;
        $pedido->save();
        $pedidos = Pedido::all();
        return view('pedidos.index', compact('pedidos'))->with('success', 'exito');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Recuperar el pedido y sus detalles de la base de datos
        $pedido = Pedido::with('users')->find($id);
        $detalles_pedidos = detalle_pedidos::where('id_pedidos', $id)->get();
        $personaliza = producPerz::where('id_pedidos', $id)->get();
        $Insumo = Insumo::all();
        // Pasar el pedido y sus detalles a la vista
        return view('pedidos.show', ['pedido' => $pedido, 'detalles_pedidos' => $detalles_pedidos, 'personaliza' => $personaliza,'Insumo'=>$Insumo]);
    }
    public function showPdf($id)
    {
        $dompdf = new Dompdf();
        $pedido = Pedido::find($id);
        $detalles_pedidos = detalle_pedidos::where('id_pedidos', $id)->get();
        $personaliza = producPerz::where('id_pedidos', $id)->get();
        $Insumo = Insumo::all();
        $dompdf->loadHtml(view('ventas.showPDF', compact('pedido', 'detalles_pedidos', 'personaliza','Insumo')));
        $dompdf->render();
        return $dompdf->stream();
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pedido = Pedido::with('users')->find($id);
        $pedidos = Pedido::find($id);
        // $personaliza = producPerz::where('id_pedidos', $id)->first();
        $personaliza = producPerz::where('id_pedidos', $id)->get();
        $users = User::all(); // define la variable $users con todos los usuarios
        $detalles_pedidos = detalle_pedidos::where('id_pedidos', $id)->get();
        return view('pedidos.edit', ['pedidos' => $pedidos, 'pedido' => $pedido, 'detalles_pedidos' => $detalles_pedidos, 'productos' => Producto::all(), 'users' => $users, 'Insumo' => Insumo::all(), 'personaliza' => $personaliza])->with('success', 'exito');
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function updateEstado(Request $request, $id)
    {
        $request->validate([
            'Estado' => 'required|max:200',
        ]);
        $pedido = Pedido::find($id);
        $pedido->Estado = $request->input('Estado');
        $pedido->save();
        return redirect()->route('pedidos.index')->with('success', 'exito')->with('success', 'Estado actualizado correctamente');;
    }

    public function updateEstadoo(Request $request, $id)
    {
        $request->validate([
            'Estadoo' => 'required|max:200',
        ]);
        $pedido = Pedido::find($id);
        $pedido->Estado = $request->input('Estado');
                // return response()->json($pedido->Estado = $request->input('Estado'));

        $pedido->save();
        return redirect()->route('pedidos.index')->with('success', 'exito')->with('success', 'Estado actualizado correctamente');;
    }

    public function update(Request $request, $id)
    {
        $productos = $request->input('ProductoID');
        $cantidades = $request->input('Cantidad');
        $personalizadosArray2 = json_decode($request->input('personalizadosArray2'), true);
        $personalizadosArray = json_decode($request->input('personalizadosArray'), true);
        if ($productos) {
            $request->validate([
                'Nombre' => 'nullable|max:500',
                'Usuario' => 'required',
                'ProductoID' => 'required|array',
                'ProductoID.*' => 'integer',
                'Total' => 'required|numeric',
            ]);
        }
        if ($personalizadosArray || $personalizadosArray2) {
            $request->validate([
                'Nombre' => 'nullable|max:500',
                'Usuario' => 'required',
                'Total' => 'required|numeric',
            ]);
        } else {
            $request->validate([
                'Nombre' => 'nullable|max:500',
                'Usuario' => 'required',
                'ProductoID' => 'required|array',
                'ProductoID.*' => 'integer',
                'Total' => 'required|numeric',
            ]);
        }
        $pedido = Pedido::find($id);
        $pedido->Nombre = $request->input('Nombre');
        $pedido->Estado = $request->input('Estado');
        $pedido->id_users = $request->input('Usuario');
        $pedido->Total = $request->input('Total');
        // return response()->json ( $pedido->Total = $request->input('Total'));
        $pedido->save();
        $pedido->detalle_pedidos()->delete();
        $total = 0;
        $pedido->productosPersonalizados()->delete();
        // return response()->json($personalizadosArray);
        if (!empty($personalizadosArray)) {
            foreach ($personalizadosArray as $personalizado) {
                // Guardar los datos del personalizado en la base de datos
                $subtotal = $personalizado['Subtotal'];
                $insumos = $personalizado['insumos'];
                $Nombre = $personalizado['Nombre'];
                // $total = +$subtotal;
                $id = '';
                foreach ($insumos as $insumo) {
                    $insumoData = explode(':', $insumo);
                    $id = trim($insumoData[0]);
                    $personalizadoModel = new producPerz();
                    $personalizadoModel->nombre = $Nombre;
                    $personalizadoModel->cantidad = 1;
                    $personalizadoModel->id_pedidos = $pedido->id;
                    $personalizadoModel->insumos = $id;
                    $personalizadoModel->Subtotal = $subtotal;
                    $personalizadoModel->save();
                }
            }
        }
        // return response()->json($personalizadoModel);
        if (!empty($personalizadosArray2)) {
            foreach ($personalizadosArray2 as $personalizado) {
                // Guardar los datos del personalizado en la base de datos
                $insumos = $personalizado['Insumos'];
                $Nombre = $personalizado['Nombre'];
                $subtotal = (int)$personalizado['Subtotal'];
                $total = $subtotal;
                foreach ($insumos as $insumo) {
                    $id = $insumo['id'];
                    $personalizadoModel = new producPerz();
                    $personalizadoModel->nombre = $Nombre;
                    $personalizadoModel->cantidad = 1;
                    $personalizadoModel->id_pedidos = $pedido->id;
                    $personalizadoModel->insumos = $id;
                    $personalizadoModel->Subtotal = $subtotal;
                    $personalizadoModel->save();
                    // Realizar el descuento del insumo asociado al producto personalizado
                    $insumo = Insumo::find($id);
                    if ($insumo) {
                        $insumo->cantidad_disponible -= 1;
                        $insumo->save();
                    }
                }
            }
        }
        if (!empty($productos)) {
            foreach ($productos as $index => $producto_id) {
                $producto = Producto::find($producto_id);
                $detalles_pedidos = new detalle_pedidos();
                $detalles_pedidos->id_pedidos = $pedido->id;
                $detalles_pedidos->cantidad = $cantidades[$index];
                $detalles_pedidos->precio_unitario = $producto->precio;
                $detalles_pedidos->id_productos = $producto_id;
                $detalles_pedidos->Nombre = $producto->nombre;
                $subtotal = $detalles_pedidos->cantidad * $detalles_pedidos->precio_unitario;
                $total += $subtotal;
                $insumo_producto = InsumoProducto::where('producto_id', $producto_id)->first();
                for ($i = 0; $i < 3; $i++) {
                    # code...
                    if ($insumo_producto) {
                        $insumo = Insumo::find($insumo_producto->insumo_id);
                        if ($insumo) {
                            $insumo->cantidad_disponible -= 3;
                            $insumo->save();
                        }
                    }
                }
                $detalles_pedidos->save();
            }
        }
            // $pedido->Total = $total;
            $pedido->save();
            $pedidos = Pedido::all();
            return view('pedidos.index', compact('pedidos'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\pedido  $pedido
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pedido = Pedido::find($id);
        // Eliminar los detalles del pedido
        detalle_pedidos::where('id_pedidos', $pedido->id)->delete();
        // Eliminar los detalles del pedido en la tabla produc_perzs
        producPerz::where('id_pedidos', $pedido->id)->delete();
        // Eliminar el pedido
        $pedido->delete();
        return redirect()->route('pedidos.index')->with('success', 'Pedido eliminado correctamente');
    }


    public function guardarPedido(Request $request)
    {
        $carrito = json_decode($request->input('carrito', '[]'), true);
        // Verificar si el carrito está vacío
        if (empty($carrito)) {
            return redirect()->back()->with('success', 'El carrito está vacío. Agrega productos antes de guardar el pedido.');
        }
        // Validar disponibilidad de insumos
        foreach ($carrito as $producto) {
            $insumo_productos = InsumoProducto::where('producto_id', $producto['id'])->get();
            foreach ($insumo_productos as $insumo_producto) {
                $insumo = Insumo::find($insumo_producto->insumo_id);
                if ($insumo && $insumo->cantidad_disponible < 3) {
                    return redirect()->back()->with('success', 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre);
                }
            }
        }
        $pedido = new Pedido();
        $pedido->Direcion = $request->input('Direcion');
        $pedido->Estado = 'En_proceso';
        $pedido->Fecha = now();
        $pedido->id_users = Auth::user()->id;
        $pedido->Total = 0;
        $pedido->save();
        $total = 0;
        // Guardar los productos del carrito en la tabla "detalle_pedidos"
        foreach ($carrito as $producto) {
            $detalle_pedido = new detalle_pedidos();
            $detalle_pedido->id_pedidos = $pedido->id;
            $detalle_pedido->cantidad = $producto['cantidad'];
            $detalle_pedido->precio_unitario = $producto['precio'];
            $detalle_pedido->id_productos = $producto['id'];
            $detalle_pedido->Nombre = $producto['nombre'];
            $subtotal = $producto['cantidad'] * $producto['precio'];
            $total += $subtotal;
            $detalle_pedido->save();
            // Realizar el descuento de 3 unidades de insumo por cada insumo asociado al producto
            $insumo_productos = InsumoProducto::where('producto_id', $producto['id'])->get();
            foreach ($insumo_productos as $insumo_producto) {
                $insumo = Insumo::find($insumo_producto->insumo_id);
                if ($insumo) {
                    $insumo->cantidad_disponible -= 3;
                    $insumo->save();
                }
            }
        }
        $productosPersonalizados = json_decode($request->input('productosPersonalizados', '[]'), true);
        // Guardar los productos personalizados en la tabla "producPerz"
        if (!empty($productosPersonalizados)) {
            $totalL = 0;
            foreach ($productosPersonalizados as $personalizado) {
                // Guardar los datos del personalizado en la base de datos
                $insumos = $personalizado['insumos'];
                $Nombre = $personalizado['Nombre'];
                $cantidad = isset($personalizado['cantidad']) && !empty($personalizado['cantidad']) ? $personalizado['cantidad'] : 1;
                // return response()->json($personalizado['cantidad']);
                $subtotal=0;
                for ($i = 0; $i < count($insumos); $i++) {
                    $subtotalData = explode(':', $insumos[$i]);
                    $subtotal += (int) trim($subtotalData[3]);
                }
               
                $id = '';
                $Nombres = '';
                foreach ($insumos as $insumo) {
                    // return response()->json($subtotal);
                    $insumoData = explode(':', $insumo);
                    $NombreData = explode(':', $Nombre);
                    $id = trim($insumoData[0]);
                    $Nombres = trim($NombreData[0]);
                    $total += $subtotal;
                    $totalL +=  $subtotal;
                    $personalizadoModel = new producPerz();
                    $personalizadoModel->nombre = $Nombres;
                    $personalizadoModel->cantidad = $cantidad;
                    $personalizadoModel->Subtotal = $totalL;
                    $personalizadoModel->id_pedidos = $pedido->id;
                    $personalizadoModel->insumos = $id;
                    $personalizadoModel->save();
                    // Realizar el descuento del insumo asociado al producto personalizado
                    $insumo = Insumo::find($id);
                    if ($insumo) {
                        $insumo->cantidad_disponible -= 1;
                        if ($insumo->cantidad_disponible < 3) {
                            $pedidos = Pedido::all();
                            return redirect()->back()->withErrors(['error' => 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre]);
                            // return view('pedidos.index', compact('pedidos'))->with('error', 'Insumo insuficiente para hacer el pedido: ' . $insumo->nombre);
                        }
                        $insumo->save();
                    }
                }
            }
        }
        // Actualizar el campo "Total" del pedido con el valor total calculado
        $pedido->Total = $total;
        $pedido->save();
        // Borrar el carrito del Local Storage después de guardar el pedido
        echo '<script>localStorage.removeItem("carrito");</script>';
        // Borrar los productos personalizados del Local Storage después de guardar el pedido
        return redirect()->route('verpedido')->with('success', 'Pedido guardado correctamente.')->with('pedidoGuardado', true);
    }



    public function verpedido()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();
        $userDirecion = Auth::user()->direccion;
        // Obtener todos los pedidos asociados al usuario
        $pedidos = Pedido::where('id_users', $user->id)->get();
        return view('cliente.pedidos', compact('pedidos', 'userDirecion'));
    }

    public function show1($id)
    {
        // Recuperar el pedido y sus detalles de la base de datos
        $pedido = Pedido::with('users')->find($id); // Cambiar 'users' por 'user'
        $detalles_pedidos = detalle_pedidos::where('id_pedidos', $id)->get();
        $personaliza = producPerz::where('id_pedidos', $id)->get();
        // Pasar el pedido y sus detalles a la vista
        return view('cliente.Detalles', compact('pedido', 'detalles_pedidos', 'personaliza'));
    }
}
