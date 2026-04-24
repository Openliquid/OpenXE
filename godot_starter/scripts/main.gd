extends Node2D

const TEAM_PLAYER := 0
const TEAM_ENEMY := 1

enum Doctrine { NONE, SWARM, FORTRESS, SPECOPS }

@onready var selection_rect: ColorRect = $UI/SelectionRect
@onready var units_root: Node2D = $Units
@onready var pings_root: Node2D = $Pings
@onready var resources_root: Node2D = $Resources
@onready var player_hq: Headquarters = $Structures/PlayerHQ
@onready var enemy_hq: Headquarters = $Structures/EnemyHQ
@onready var beacon: ControlBeacon = $Beacon
@onready var camera: Camera2D = $Camera2D
@onready var money_label: Label = $UI/HUD/VBox/MoneyLabel
@onready var doctrine_label: Label = $UI/HUD/VBox/DoctrineLabel
@onready var objective_label: Label = $UI/HUD/VBox/ObjectiveLabel
@onready var info_label: Label = $UI/HUD/VBox/InfoLabel

@export var unit_scene: PackedScene
@export var ping_scene: PackedScene

var drag_selecting: bool = false
var drag_start: Vector2
var selected_units: Array[RTSUnit] = []
var player_money: float = 300.0
var attack_move_armed: bool = false
var doctrine: Doctrine = Doctrine.NONE
var beacon_points_player: int = 0
var beacon_points_enemy: int = 0

var ai_spawn_cd: float = 0.0
var ai_attack_cd: float = 0.0

func _ready() -> void:
    _refresh_ui()
    player_hq.destroyed.connect(_on_hq_destroyed)
    enemy_hq.destroyed.connect(_on_hq_destroyed)
    beacon.captured.connect(_on_beacon_captured)

func _unhandled_input(event: InputEvent) -> void:
    if event is InputEventMouseButton and event.button_index == MOUSE_BUTTON_LEFT:
        if event.pressed:
            drag_selecting = true
            drag_start = event.position
            selection_rect.position = drag_start
            selection_rect.size = Vector2.ZERO
            selection_rect.visible = true
        else:
            drag_selecting = false
            _apply_selection()
            selection_rect.visible = false

    if event is InputEventMouseButton and event.button_index == MOUSE_BUTTON_RIGHT and event.pressed:
        var queued := Input.is_key_pressed(KEY_SHIFT)
        _issue_context_command(event.position, queued)
        attack_move_armed = false

    if event is InputEventKey and event.pressed:
        match event.keycode:
            KEY_1:
                _spawn_player_unit(RTSUnit.UnitRole.SOLDIER)
            KEY_2:
                _spawn_player_unit(RTSUnit.UnitRole.HARVESTER)
            KEY_3:
                _spawn_player_unit(RTSUnit.UnitRole.SCOUT)
            KEY_4:
                _spawn_player_unit(RTSUnit.UnitRole.TANK)
            KEY_A:
                attack_move_armed = true
                info_label.text = "Attack-Move aktiv: RMB setzen"
            KEY_F1:
                _set_doctrine(Doctrine.SWARM)
            KEY_F2:
                _set_doctrine(Doctrine.FORTRESS)
            KEY_F3:
                _set_doctrine(Doctrine.SPECOPS)
            KEY_R:
                _radar_ping()

func _process(delta: float) -> void:
    _update_selection_box()
    _camera_controls(delta)
    _resource_loop(delta)
    _ai_loop(delta)
    _objective_loop(delta)
    _visibility_loop()

func _update_selection_box() -> void:
    if drag_selecting:
        var now: Vector2 = get_viewport().get_mouse_position()
        var top_left := Vector2(min(drag_start.x, now.x), min(drag_start.y, now.y))
        var size := Vector2(abs(now.x - drag_start.x), abs(now.y - drag_start.y))
        selection_rect.position = top_left
        selection_rect.size = size

func _camera_controls(delta: float) -> void:
    var dir := Vector2.ZERO
    if Input.is_key_pressed(KEY_A) and not attack_move_armed:
        dir.x -= 1
    if Input.is_key_pressed(KEY_D):
        dir.x += 1
    if Input.is_key_pressed(KEY_W):
        dir.y -= 1
    if Input.is_key_pressed(KEY_S):
        dir.y += 1
    if dir != Vector2.ZERO:
        camera.global_position += dir.normalized() * 450.0 * delta

func _apply_selection() -> void:
    for unit in selected_units:
        unit.set_selected(false)
    selected_units.clear()

    var rect := Rect2(selection_rect.position, selection_rect.size)
    for child in units_root.get_children():
        if child is RTSUnit:
            var unit := child as RTSUnit
            if unit.team_id == TEAM_PLAYER and rect.has_point(unit.global_position):
                unit.set_selected(true)
                selected_units.append(unit)

func _issue_context_command(screen_pos: Vector2, queued: bool) -> void:
    if selected_units.is_empty():
        return

    var clicked_target := _find_attackable_target_at(screen_pos)
    if clicked_target != null:
        for unit in selected_units:
            unit.issue_attack(clicked_target)
        return

    for i in selected_units.size():
        var offset := Vector2((i % 4) * 18.0, (i / 4) * 18.0)
        selected_units[i].issue_move(screen_pos + offset, queued, attack_move_armed)

func _find_attackable_target_at(pos: Vector2) -> Node2D:
    var candidates: Array[Node2D] = []
    for child in units_root.get_children():
        if child is RTSUnit:
            var unit := child as RTSUnit
            if unit.team_id == TEAM_ENEMY and unit.visible:
                candidates.append(unit)
    if enemy_hq.visible:
        candidates.append(enemy_hq)

    for target in candidates:
        if not is_instance_valid(target):
            continue
        if target.global_position.distance_to(pos) < 28.0:
            return target
    return null

func _resource_loop(_delta: float) -> void:
    for child in units_root.get_children():
        if child is RTSUnit:
            var unit := child as RTSUnit
            if not unit.can_harvest or unit.team_id != TEAM_PLAYER:
                continue

            if unit.carrying_amount <= 0.0:
                var node := _nearest_resource(unit.global_position)
                if node != null:
                    unit.issue_move(node.global_position)
                    if unit.global_position.distance_to(node.global_position) < 22.0:
                        unit.carrying_amount += node.harvest(unit.carry_capacity)
            else:
                unit.issue_move(player_hq.global_position)
                if unit.global_position.distance_to(player_hq.global_position) < 42.0:
                    player_money += unit.carrying_amount
                    if doctrine == Doctrine.SWARM:
                        player_money += 2.0
                    unit.carrying_amount = 0.0
                    _refresh_ui()

func _nearest_resource(origin: Vector2) -> ResourceNode:
    var best: ResourceNode = null
    var best_dist := INF
    for child in resources_root.get_children():
        if child is ResourceNode:
            var node := child as ResourceNode
            var d := origin.distance_squared_to(node.global_position)
            if d < best_dist:
                best_dist = d
                best = node
    return best

func _spawn_player_unit(role: RTSUnit.UnitRole) -> void:
    var cost := _role_cost(role)
    if doctrine == Doctrine.SWARM and role == RTSUnit.UnitRole.SOLDIER:
        cost -= 20.0

    if player_money < cost:
        info_label.text = "Nicht genug Ressourcen"
        return

    player_money -= cost
    var u := unit_scene.instantiate() as RTSUnit
    u.global_position = player_hq.global_position + Vector2(randf_range(-60, 60), randf_range(-60, 60))
    u.team_id = TEAM_PLAYER
    _apply_role_stats(u, role)
    units_root.add_child(u)
    _refresh_ui()

func _apply_role_stats(unit: RTSUnit, role: RTSUnit.UnitRole) -> void:
    unit.role = role
    match role:
        RTSUnit.UnitRole.SOLDIER:
            unit.can_harvest = false
            unit.move_speed = 220.0
            unit.max_health = 120.0
            unit.attack_damage = 10.0
            unit.attack_range = 90.0
        RTSUnit.UnitRole.HARVESTER:
            unit.can_harvest = true
            unit.move_speed = 170.0
            unit.max_health = 90.0
            unit.attack_damage = 2.0
            unit.attack_range = 65.0
            unit.carry_capacity = 35.0
        RTSUnit.UnitRole.SCOUT:
            unit.can_harvest = false
            unit.move_speed = 310.0
            unit.max_health = 70.0
            unit.attack_damage = 6.0
            unit.attack_range = 80.0
            unit.aggro_range = 230.0
        RTSUnit.UnitRole.TANK:
            unit.can_harvest = false
            unit.move_speed = 140.0
            unit.max_health = 240.0
            unit.attack_damage = 18.0
            unit.attack_range = 120.0
            unit.attack_cooldown = 1.3

    if unit.team_id == TEAM_PLAYER:
        if doctrine == Doctrine.FORTRESS:
            unit.max_health *= 1.15
            unit.health = unit.max_health
        elif doctrine == Doctrine.SPECOPS:
            unit.move_speed *= 1.12
            unit.aggro_range *= 1.2

func _role_cost(role: RTSUnit.UnitRole) -> float:
    match role:
        RTSUnit.UnitRole.SOLDIER:
            return 100.0
        RTSUnit.UnitRole.HARVESTER:
            return 150.0
        RTSUnit.UnitRole.SCOUT:
            return 130.0
        RTSUnit.UnitRole.TANK:
            return 260.0
    return 100.0

func _ai_loop(delta: float) -> void:
    ai_spawn_cd -= delta
    ai_attack_cd -= delta

    if ai_spawn_cd <= 0.0:
        ai_spawn_cd = 4.0
        var enemy := unit_scene.instantiate() as RTSUnit
        enemy.global_position = enemy_hq.global_position + Vector2(randf_range(-50, 50), randf_range(-50, 50))
        enemy.team_id = TEAM_ENEMY
        _apply_role_stats(enemy, RTSUnit.UnitRole.SOLDIER if randi() % 2 == 0 else RTSUnit.UnitRole.TANK)
        units_root.add_child(enemy)

    if ai_attack_cd <= 0.0:
        ai_attack_cd = 2.0
        for child in units_root.get_children():
            if child is RTSUnit:
                var unit := child as RTSUnit
                if unit.team_id == TEAM_ENEMY:
                    var target := _closest_player_unit(unit.global_position)
                    if target != null:
                        unit.issue_attack(target)
                    else:
                        unit.issue_attack(player_hq)

func _closest_player_unit(origin: Vector2) -> RTSUnit:
    var best: RTSUnit = null
    var best_dist := INF
    for child in units_root.get_children():
        if child is RTSUnit:
            var unit := child as RTSUnit
            if unit.team_id != TEAM_PLAYER:
                continue
            var d := origin.distance_squared_to(unit.global_position)
            if d < best_dist:
                best_dist = d
                best = unit
    return best

func _objective_loop(delta: float) -> void:
    var all_units: Array[RTSUnit] = []
    for child in units_root.get_children():
        if child is RTSUnit:
            all_units.append(child as RTSUnit)
    beacon.update_control(all_units, delta)

func _on_beacon_captured(team_id: int) -> void:
    if team_id == TEAM_PLAYER:
        beacon_points_player += 1
    elif team_id == TEAM_ENEMY:
        beacon_points_enemy += 1

    if beacon_points_player >= 3:
        info_label.text = "Sieg - Beacon-Dominanz"
    elif beacon_points_enemy >= 3:
        info_label.text = "Niederlage - Beacon verloren"

    _refresh_ui()

func _set_doctrine(next: Doctrine) -> void:
    if doctrine != Doctrine.NONE:
        info_label.text = "Doktrin bereits gewählt"
        return

    doctrine = next
    match doctrine:
        Doctrine.SWARM:
            doctrine_label.text = "Doktrin: Swarm (billige Soldiers + Eco-Bonus)"
        Doctrine.FORTRESS:
            doctrine_label.text = "Doktrin: Fortress (+15% HP)"
            player_hq.max_health *= 1.25
            player_hq.health = player_hq.max_health
        Doctrine.SPECOPS:
            doctrine_label.text = "Doktrin: SpecOps (+Mobility +Aggro)"
    _refresh_ui()

func _visibility_loop() -> void:
    var player_units: Array[RTSUnit] = []
    for c in units_root.get_children():
        if c is RTSUnit and (c as RTSUnit).team_id == TEAM_PLAYER:
            player_units.append(c as RTSUnit)

    for c in units_root.get_children():
        if c is RTSUnit and (c as RTSUnit).team_id == TEAM_ENEMY:
            var enemy := c as RTSUnit
            var seen := false
            for p in player_units:
                var sight := 250.0
                if p.role == RTSUnit.UnitRole.SCOUT:
                    sight = 360.0
                if p.global_position.distance_to(enemy.global_position) <= sight:
                    seen = true
                    break
            enemy.visible = seen
            enemy.modulate.a = 1.0 if seen else 0.18

    var hq_seen := false
    for p in player_units:
        var sight2 := 260.0
        if p.role == RTSUnit.UnitRole.SCOUT:
            sight2 = 380.0
        if p.global_position.distance_to(enemy_hq.global_position) <= sight2:
            hq_seen = true
            break
    enemy_hq.visible = hq_seen
    enemy_hq.modulate.a = 1.0 if hq_seen else 0.25

func _radar_ping() -> void:
    if ping_scene == null:
        return
    for c in units_root.get_children():
        if c is RTSUnit:
            var u := c as RTSUnit
            if u.team_id == TEAM_ENEMY:
                var p := ping_scene.instantiate() as Node2D
                p.global_position = u.global_position
                pings_root.add_child(p)
    var hq_ping := ping_scene.instantiate() as Node2D
    hq_ping.global_position = enemy_hq.global_position
    pings_root.add_child(hq_ping)
    info_label.text = "Radar-Ping ausgelöst"

func _on_hq_destroyed(team_id: int) -> void:
    if team_id == TEAM_PLAYER:
        info_label.text = "Niederlage - HQ zerstört"
    else:
        info_label.text = "Sieg - Gegnerbasis zerstört"

func _refresh_ui() -> void:
    money_label.text = "Credits: %d" % int(player_money)
    objective_label.text = "Beacon-Punkte: %d / %d" % [beacon_points_player, beacon_points_enemy]
    if doctrine == Doctrine.NONE:
        doctrine_label.text = "Doktrin: ungewählt (F1/F2/F3)"
    if info_label.text.is_empty():
        info_label.text = "1/2/3/4 Units | A Attack-Move | Shift Queue | F1/F2/F3 | R Radar"
