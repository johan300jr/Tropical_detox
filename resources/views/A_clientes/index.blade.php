@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
      <h3 class="page__heading">Clientes</h3>
  </div>
      <div class="section-body">
          <div class="row">
              <div class="col-lg-12">
                  <div class="card">
                      <div class="card-body">                           
                          <a class="btn btn-warning" href="{{ route('A_clientes.create') }}">Nuevo</a>        

                          

                        
                          <table id="example" class="table table-striped table-bordered" style="width:100%">
                            <thead style="background-color:#6777ef">
                                <th style="color:#fff;">ID</th>
                                <th style="color:#fff;">Nombre</th>
                                <th style="color:#fff;">Apellidos</th>
                                <th style="color:#fff;">Estado</th>
                                <th style="color:#fff;">Documento</th>
                                <th style="color:#fff;">Teléfono</th>
                                <th style="color:#fff;">Dirección</th>
                                <th style="color:#fff;">E-mail</th>
                                <th style="color:#fff;">Acciones</th>
                            </thead>
                            <tbody>
                                @foreach ($usuarios as $usuario)
                                    @foreach ($usuario->roles as $rol)
                                        @if ($rol->name === 'cliente')
                                            <tr>
                                                <td>{{ $usuario->id }}</td>
                                                <td>{{ $usuario->name }}</td>
                                                <td>{{ $usuario->apellidos }}</td>
                                                <td>
                                                    @if ($usuario->estado)
                                                        Activo
                                                    @else
                                                        Inactivo
                                                    @endif
                                                </td>
                                                <td>{{ $usuario->documento }}</td>
                                                <td>{{ $usuario->telefono }}</td>
                                                <td>{{ $usuario->direccion }}</td>
                                                <td>{{ $usuario->email }}</td>
                                                <td>
                                                    <a class="btn btn-sm btn-success" href="{{ route('A_clientes.edit', $usuario->id) }}"><i class="fa fa-fw fa-edit"></i>Editar</a>
                                                    <a class="btn btn-sm btn-primary" href="{{ route('A_clientes.show', $usuario->id) }}"><i class="fa fa-fw fa-eye"></i>Mostrar</a>
                                                  
                                                    {!! Form::open([
                                                      'method' => 'DELETE',
                                                      'route' => ['A_clientes.destroy', $usuario->id],
                                                      'style' => 'display:inline',
                                                      'onsubmit' => 'confirmDelete(event, this)',
                                                    ]) !!}
                                                      <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i>Eliminar</button>
                                                    {!! Form::close() !!}
                                                  </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                            <!-- Centramos la paginacion a la derecha -->
                             
                            
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </section>
    <script>
        $(document).ready(function() {
            var table = $('#example').DataTable({
                responsive: true
            });
    
            new $.fn.dataTable.FixedHeader(table);
        });
    </script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function confirmDelete(event, form) {
    event.preventDefault();
    
    Swal.fire({
      title: '¿Estás seguro?',
      text: 'Esta acción eliminará al cliente. No podrás deshacer esta acción.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  }
</script>
@endsection

