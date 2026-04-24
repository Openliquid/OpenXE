# Unreal RTS Starter (C&C-style)

Dieses Starter-Paket ist jetzt so aufgebaut, dass du es direkt als UE5-C++-Projekt öffnen kannst (`unreal_starter/OpenXERTS.uproject`).

## 1) Schnellstart (Projekt wirklich starten)

1. **Unreal Engine 5.4+** installieren.
2. Im Dateisystem zu `unreal_starter/` gehen.
3. `OpenXERTS.uproject` doppelklicken.
4. Wenn gefragt: C++-Projektdateien generieren und kompilieren.
5. Projekt im Editor öffnen und eine Map unter `Content/RTS/Maps` anlegen.

> Falls der Doppelklick nicht funktioniert, im Epic Launcher Unreal öffnen und die `.uproject` manuell wählen.

## 2) Ziel-MVP

- RTS-Kamera (Pan/Zoom/Rotate)
- Box-Selection und Rechtsklick-Move
- 1 Ressource + Sammler
- HQ + Kaserne
- Infanterie + Sammler
- Auto-Attack in Reichweite
- Einfache KI (spawnt Einheiten und greift an)

## 3) Projektstruktur in diesem Repo

- `unreal_starter/OpenXERTS.uproject`
- `unreal_starter/Source/OpenXERTS/` (C++ Modul)
- `unreal_starter/Source/OpenXERTS.Target.cs`
- `unreal_starter/Source/OpenXERTSEditor.Target.cs`

## 4) Bereits enthaltene C++-Basisklassen

- `RTSPlayerController` (Input + Selection + Commands)
- `RTSUnitBase` (HP, Move, Attack)

### RTSUnitBase: Verhalten

- Variablen:
  - `TeamId` (int32)
  - `MaxHealth`, `CurrentHealth`
  - `AttackRange`, `AttackDamage`, `AttackInterval`
  - `TargetActor`
- Funktionen:
  - `IssueMove(FVector)`
  - `IssueAttack(AActor*)`
  - `TakeRTSDamage(float)`

## 5) Blueprint-Setup (im Editor)

1. `BP_RTSPlayerController` von `RTSPlayerController`.
2. `BP_RTSPawn` als Kamera-Pawn (neu erstellen).
3. `BP_UnitBase` von `RTSUnitBase`.
4. `BP_Soldier`, `BP_Harvester` als Child-Blueprints von `BP_UnitBase`.
5. Optional: `BP_BuildingBase`, `BP_HQ`, `BP_Barracks` ergänzen.

## 6) Input Mapping

Wenn du klassisches Input nutzt:
- Action `Select` auf LMB
- Action `Command` auf RMB

(Enhanced Input Plugin ist im `.uproject` bereits aktiviert.)

## 7) Was als Nächstes fehlt, um „C&C-Feeling“ zu bekommen

1. RTS-Kamera-Pawn (`BP_RTSPawn`) fertig bauen
2. Mehrfachauswahl (Drag-Box) ergänzen
3. Ressourcenkreislauf (Ernte -> Abgabe -> Konto)
4. Produktions-Queue in Gebäuden
5. Gegner-KI + Win/Lose Conditions

## 8) Realistische Erwartung

- **Ja, starten kannst du jetzt** (Projektdateien + Modul sind vorhanden).
- Für ein wirklich spielbares Match fehlen noch die oben genannten Gameplay-Systeme und Blueprints.
