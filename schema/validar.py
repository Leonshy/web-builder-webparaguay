#!/usr/bin/env python3
"""
Valida una configuracion de sitio contra site.schema.json.

Uso:  python3 validar.py <archivo.json> [schema.json]
Sale con codigo 0 si valida, 1 si no.
"""
import json, sys
from pathlib import Path

try:
    from jsonschema import Draft202012Validator
except ImportError:
    sys.exit("Falta la dependencia: pip install jsonschema")

def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    inst_path = Path(sys.argv[1])
    schema_path = Path(sys.argv[2]) if len(sys.argv) > 2 else inst_path.parent / "site.schema.json"

    schema = json.loads(schema_path.read_text(encoding="utf-8"))
    inst = json.loads(inst_path.read_text(encoding="utf-8"))

    Draft202012Validator.check_schema(schema)
    errores = sorted(Draft202012Validator(schema).iter_errors(inst),
                     key=lambda e: list(e.absolute_path))

    if not errores:
        print(f"OK  {inst_path.name} valida contra {schema_path.name}")
        return 0

    print(f"FALLA  {len(errores)} error(es) en {inst_path.name}\n")
    for e in errores:
        ruta = "/".join(map(str, e.absolute_path)) or "(raiz)"
        print(f"  {ruta}\n    {e.message}\n")
    return 1

if __name__ == "__main__":
    sys.exit(main())
