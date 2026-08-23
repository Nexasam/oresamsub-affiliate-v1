import { usePage } from "@inertiajs/react";
import DashboardLayoutV1 from "@/Layouts/DashboardLayout";
import DashboardLayoutV2 from "@/Layouts/DashboardLayoutV2";

export default function CustomerLayout(props) {
  const { customerUi } = usePage().props;

  return customerUi?.version === "v2"
    ? <DashboardLayoutV2 {...props} />
    : <DashboardLayoutV1 {...props} />;
}
