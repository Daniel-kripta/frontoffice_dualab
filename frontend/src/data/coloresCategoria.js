// Colores de categoría del blog (lista blanca en backend: config/cms.php → colores_categoria).
// Texto en las variantes oscuras de marca para llegar a 4,5:1 sobre el fondo claro.
export const COLORES_CATEGORIA = {
  centros:          { nombre: 'Azul centros',     clase: 'bg-centros/10 text-centros',             punto: 'bg-centros' },
  empresas:         { nombre: 'Verde empresas',   clase: 'bg-empresas/10 text-[#3D7A1E]',          punto: 'bg-empresas' },
  alumnos:          { nombre: 'Naranja alumnado', clase: 'bg-alumnos/15 text-[#B85E0A]',           punto: 'bg-alumnos' },
  administraciones: { nombre: 'Turquesa',         clase: 'bg-administraciones/10 text-[#0F7C7D]',  punto: 'bg-administraciones' },
  'azul-noche':     { nombre: 'Azul noche',       clase: 'bg-azul-noche/10 text-azul-noche',       punto: 'bg-azul-noche' },
  violeta:          { nombre: 'Violeta',          clase: 'bg-violet-100 text-violet-700',          punto: 'bg-violet-500' },
  ambar:            { nombre: 'Ámbar',            clase: 'bg-amber-100 text-amber-700',            punto: 'bg-amber-500' },
}

export const claseCategoria = color => COLORES_CATEGORIA[color]?.clase ?? COLORES_CATEGORIA.centros.clase
