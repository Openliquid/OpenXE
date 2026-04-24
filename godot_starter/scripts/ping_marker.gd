extends Node2D
class_name PingMarker

@export var duration: float = 3.0

@onready var ring: Line2D = $Ring
var ttl: float

func _ready() -> void:
    ttl = duration

func _process(delta: float) -> void:
    ttl -= delta
    ring.width = 1.0 + sin(Time.get_ticks_msec() * 0.01) * 0.5 + 1.5
    if ttl <= 0.0:
        queue_free()
