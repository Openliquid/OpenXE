extends CharacterBody2D
class_name RTSUnit

signal died(unit: RTSUnit)

enum UnitRole { SOLDIER, HARVESTER, SCOUT, TANK }

@export var team_id: int = 0
@export var role: UnitRole = UnitRole.SOLDIER
@export var move_speed: float = 220.0
@export var max_health: float = 100.0
@export var attack_damage: float = 8.0
@export var attack_range: float = 90.0
@export var aggro_range: float = 180.0
@export var attack_cooldown: float = 0.8
@export var can_harvest: bool = false
@export var carry_capacity: float = 25.0

var health: float
var selected: bool = false
var move_target: Vector2
var attack_target: Node2D
var carrying_amount: float = 0.0
var command_queue: Array[Vector2] = []
var attack_move_mode: bool = false

@onready var selection_outline: Line2D = $Selection
@onready var hp_bar: ProgressBar = $HPBar

var _attack_timer: float = 0.0

func _ready() -> void:
    add_to_group("damageable")
    health = max_health
    move_target = global_position
    hp_bar.max_value = max_health
    hp_bar.value = health
    _update_selection_visual()

func _physics_process(delta: float) -> void:
    _attack_timer = max(_attack_timer - delta, 0.0)

    if attack_move_mode and not is_instance_valid(attack_target):
        attack_target = _find_enemy_in_aggro()

    if is_instance_valid(attack_target):
        var distance := global_position.distance_to(attack_target.global_position)
        if distance <= attack_range:
            velocity = Vector2.ZERO
            _try_attack()
        else:
            _move_towards(attack_target.global_position)
    else:
        _move_towards(move_target)
        if global_position.distance_to(move_target) < 4.0 and command_queue.size() > 0:
            move_target = command_queue.pop_front()

    move_and_slide()

func issue_move(target: Vector2, queued: bool = false, attack_move: bool = false) -> void:
    if not queued:
        command_queue.clear()
        attack_target = null
    attack_move_mode = attack_move
    if queued:
        command_queue.append(target)
    else:
        move_target = target

func issue_attack(target: Node2D) -> void:
    if target == self:
        return
    if target is RTSUnit and (target as RTSUnit).team_id == team_id:
        return
    attack_target = target
    attack_move_mode = false

func set_selected(value: bool) -> void:
    selected = value
    _update_selection_visual()

func apply_damage(amount: float) -> void:
    health = max(health - amount, 0.0)
    hp_bar.value = health
    if health <= 0.0:
        died.emit(self)
        queue_free()

func _move_towards(target: Vector2) -> void:
    var to_target := target - global_position
    if to_target.length() < 3.0:
        velocity = Vector2.ZERO
    else:
        velocity = to_target.normalized() * move_speed

func _try_attack() -> void:
    if _attack_timer > 0.0:
        return
    _attack_timer = attack_cooldown
    if is_instance_valid(attack_target) and attack_target.has_method("apply_damage"):
        attack_target.call("apply_damage", attack_damage)

func _find_enemy_in_aggro() -> Node2D:
    var candidates := get_tree().get_nodes_in_group("damageable")
    var best: Node2D = null
    var best_dist := INF
    for n in candidates:
        if n == self:
            continue
        if n is RTSUnit and (n as RTSUnit).team_id == team_id:
            continue
        if n is Headquarters and (n as Headquarters).team_id == team_id:
            continue
        if n is Node2D:
            var d := global_position.distance_squared_to((n as Node2D).global_position)
            if d < best_dist and d <= aggro_range * aggro_range:
                best_dist = d
                best = n
    return best

func _update_selection_visual() -> void:
    selection_outline.visible = selected
