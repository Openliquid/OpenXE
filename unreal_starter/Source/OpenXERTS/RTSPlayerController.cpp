#include "RTSPlayerController.h"
#include "RTSUnitBase.h"
#include "Engine/World.h"

ARTSPlayerController::ARTSPlayerController()
{
    bShowMouseCursor = true;
    bEnableClickEvents = true;
    bEnableMouseOverEvents = true;
}

void ARTSPlayerController::SetupInputComponent()
{
    Super::SetupInputComponent();

    check(InputComponent);
    InputComponent->BindAction("Select", IE_Pressed, this, &ARTSPlayerController::HandleLeftClick);
    InputComponent->BindAction("Command", IE_Pressed, this, &ARTSPlayerController::HandleRightClick);
}

void ARTSPlayerController::HandleLeftClick()
{
    SelectSingleUnderCursor();
}

void ARTSPlayerController::HandleRightClick()
{
    CommandMoveOrAttackUnderCursor();
}

void ARTSPlayerController::SelectSingleUnderCursor()
{
    FHitResult Hit;
    GetHitResultUnderCursor(ECC_Visibility, false, Hit);

    SelectedUnits.Empty();
    if (ARTSUnitBase* Unit = Cast<ARTSUnitBase>(Hit.GetActor()))
    {
        SelectedUnits.Add(Unit);
    }
}

void ARTSPlayerController::CommandMoveOrAttackUnderCursor()
{
    if (SelectedUnits.IsEmpty())
    {
        return;
    }

    FHitResult Hit;
    GetHitResultUnderCursor(ECC_Visibility, false, Hit);

    if (ARTSUnitBase* TargetUnit = Cast<ARTSUnitBase>(Hit.GetActor()))
    {
        for (ARTSUnitBase* Unit : SelectedUnits)
        {
            if (IsValid(Unit) && Unit != TargetUnit)
            {
                Unit->IssueAttack(TargetUnit);
            }
        }
        return;
    }

    const FVector Dest = Hit.ImpactPoint;
    for (ARTSUnitBase* Unit : SelectedUnits)
    {
        if (IsValid(Unit))
        {
            Unit->IssueMove(Dest);
        }
    }
}
