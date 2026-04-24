using UnrealBuildTool;

public class OpenXERTS : ModuleRules
{
    public OpenXERTS(ReadOnlyTargetRules Target) : base(Target)
    {
        PCHUsage = PCHUsageMode.UseExplicitOrSharedPCHs;

        PublicDependencyModuleNames.AddRange(new[]
        {
            "Core",
            "CoreUObject",
            "Engine",
            "InputCore",
            "AIModule",
            "EnhancedInput"
        });
    }
}
