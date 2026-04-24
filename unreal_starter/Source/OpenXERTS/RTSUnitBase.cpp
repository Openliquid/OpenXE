#include "RTSUnitBase.h"
#include "AIController.h"

ARTSUnitBase::ARTSUnitBase()
{
    PrimaryActorTick.bCanEverTick = true;
}

void ARTSUnitBase::BeginPlay()
{
    Super::BeginPlay();
    CurrentHealth = MaxHealth;
}

void ARTSUnitBase::Tick(float DeltaSeconds)
{
    Super::Tick(DeltaSeconds);
    TryAttack(DeltaSeconds);
}

void ARTSUnitBase::IssueMove(const FVector& Destination)
{
    if (AAIController* AI = Cast<AAIController>(GetController()))
    {
        AI->MoveToLocation(Destination, 10.f, true, true, false);
    }
}

void ARTSUnitBase::IssueAttack(AActor* InTarget)
{
    TargetActor = InTarget;
}

void ARTSUnitBase::TakeRTSDamage(float DamageAmount)
{
    CurrentHealth = FMath::Clamp(CurrentHealth - DamageAmount, 0.f, MaxHealth);
    if (CurrentHealth <= 0.f)
    {
        Destroy();
    }
}

void ARTSUnitBase::TryAttack(float DeltaSeconds)
{
    if (!IsValid(TargetActor))
    {
        return;
    }

    TimeSinceLastAttack += DeltaSeconds;
    if (TimeSinceLastAttack < AttackInterval)
    {
        return;
    }

    const float Dist = FVector::Dist(GetActorLocation(), TargetActor->GetActorLocation());
    if (Dist > AttackRange)
    {
        if (AAIController* AI = Cast<AAIController>(GetController()))
        {
            AI->MoveToActor(TargetActor, AttackRange * 0.8f);
        }
        return;
    }

    TimeSinceLastAttack = 0.f;

    if (ARTSUnitBase* UnitTarget = Cast<ARTSUnitBase>(TargetActor))
    {
        UnitTarget->TakeRTSDamage(AttackDamage);
    }
}
