# Godot RTS Starter (mac-freundlich, vernetzt)

Dieses Godot-4-Starterprojekt ist jetzt ein **vernetztes RTS-MVP+** mit nächsten Ausbaustufen:

- Drag-Selection + Kontextbefehle
- Attack-Move (`A` + RMB)
- Command Queue (Shift + RMB)
- Einheitenrollen: Soldier, Harvester, Scout, Tank
- Einheiten mit HP, Aggro-Reichweite, Auto-Attack, Cooldowns
- Ressourcenknoten + Harvester + Credits
- Spieler-/Gegner-HQ als zerstörbare Ziele
- Einfache KI-Wellen (inkl. Tank-Soldier-Mix)
- Doktrin-System (F1/F2/F3): Swarm / Fortress / SpecOps
- Sekundärziel: Control Beacon (Punkte-Win Condition)
- **Fog-of-War light** (Gegner sichtbar bei Sichtkontakt)
- **Radar-Pings** (`R`) mit temporären Marker-Echos
- Produktion über Hotkeys (1..4)
- WASD-Kamera

## Starten auf macOS

1. Godot 4.x installieren.
2. `godot_starter/project.godot` im Project Manager importieren.
3. Projekt starten (`Play`).

## Controls

- **LMB halten + ziehen**: Auswahlrechteck
- **RMB**: Kontextbefehl (Attack, falls Ziel getroffen; sonst Move)
- **Shift + RMB**: Command Queue
- **A, dann RMB**: Attack-Move
- **1/2/3/4**: Soldier/Harvester/Scout/Tank produzieren
- **F1/F2/F3**: Doktrin wählen (einmalig pro Match)
- **R**: Radar-Ping
- **WASD**: Kamera bewegen

## Ganzheitlicher Loop

1. Harvester sichern Credits.
2. Credits finanzieren Unit-Komposition.
3. Doktrin prägt deinen Mid-/Lategame-Stil.
4. Fog + Scout-Vision bestimmen Informationskontrolle.
5. Beacon-Kontrolle erzwingt Map-Präsenz.
6. HQ-Fall **oder** Beacon-Dominanz entscheidet das Match.

## Wichtige Dateien

- `godot_starter/scenes/Main.tscn`
- `godot_starter/scenes/Beacon.tscn`
- `godot_starter/scenes/PingMarker.tscn`
- `godot_starter/scenes/Unit.tscn`
- `godot_starter/scenes/Headquarters.tscn`
- `godot_starter/scenes/ResourceNode.tscn`
- `godot_starter/scripts/main.gd`
- `godot_starter/scripts/beacon.gd`
- `godot_starter/scripts/ping_marker.gd`
- `godot_starter/scripts/unit.gd`
- `godot_starter/scripts/headquarters.gd`
- `godot_starter/scripts/resource_node.gd`
