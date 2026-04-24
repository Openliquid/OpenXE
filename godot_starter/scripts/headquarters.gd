extends Node2D
class_name Headquarters

signal destroyed(team_id: int)

@export var team_id: int = 0
@export var max_health: float = 800.0

var health: float

@onready var hp_bar: ProgressBar = $HPBar

func _ready() -> void:
    add_to_group("damageable")
    health = max_health
    hp_bar.max_value = max_health
    hp_bar.value = health

func apply_damage(amount: float) -> void:
    health = max(health - amount, 0.0)
    hp_bar.value = health
    if health <= 0.0:
        destroyed.emit(team_id)
        queue_free()
