<?php

namespace App\Http\Controllers;

use App\Models\IpgDevice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\IpgSerial;
use App\Exports\IpgDevicesExport;
use App\Exports\IpgModelsExport;
use Maatwebsite\Excel\Facades\Excel;



class IpgDeviceController extends Controller
{
    public function exportToExcel()
    {
        // return Excel::download(new IpgDevicesExport, 'ipg_devices.csv');
        return Excel::download(new IpgDevicesExport, 'ipg_devices.xlsx');

    }

     /**
     * Export model details including model number, name, type, warranty, CM and MR
     */
    public function exportModelDetails()
    {
        return Excel::download(new IpgModelsExport, 'ipg_model_details.csv');
    }
    


    /**
     * Display a listing of IPG devices.
     */
    public function index()
    {
        $devices = IpgDevice::all();
        return response()->json(['devices' => $devices]);
    }

    /**
     * Store a new IPG device.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ipg_serial_number' => 'required|string|unique:ipg_devices',
            'ipg_model_name' => 'required|string',
            'ipg_model_number' => 'required|string',
        ]);

        $device = IpgDevice::create($validated);

        return response()->json([
            'message' => 'IPG device added successfully',
            'device' => $device
        ], 201);
    }

    /**
     * Link an IPG device to a patient and implant.
     */
    public function linkToPatient(Request $request)
    {
        $validated = $request->validate([
            'ipg_serial_number' => 'required|string|exists:ipg_devices,ipg_serial_number',
            'patient_id' => 'required|exists:patients,id',
            'implant_id' => 'required|exists:implants,id',
        ]);

        $device = IpgDevice::where('ipg_serial_number', $validated['ipg_serial_number'])->first();

        if ($device->is_linked) {
            return response()->json([
                'message' => 'This IPG device is already linked to a patient'
            ], 400);
        }

        $device->update([
            'patient_id' => $validated['patient_id'],
            'implant_id' => $validated['implant_id'],
            'is_linked' => true
        ]);

        return response()->json([
            'message' => 'IPG device linked successfully',
            'device' => $device
        ]);
    }

    /**
     * Unlink an IPG device from a patient.
     */
    public function unlinkFromPatient(Request $request)
    {
        $validated = $request->validate([
            'ipg_serial_number' => 'required|string|exists:ipg_devices,ipg_serial_number',
        ]);

        $device = IpgDevice::where('ipg_serial_number', $validated['ipg_serial_number'])->first();

        $device->update([
            'patient_id' => null,
            'implant_id' => null,
            'is_linked' => false
        ]);

        return response()->json([
            'message' => 'IPG device unlinked successfully',
            'device' => $device
        ]);
    }

    /**
     * Get available (unlinked) IPG devices.
     */
    public function getAvailableDevices()
    {
        $devices = IpgDevice::where('is_linked', false)->get();
        return response()->json(['devices' => $devices]);
    }

    /**
     * Get available  IPG devices detials (ipg_model_name, ipg_model_number).
     */
    public function getDeviceBySerialNumber($serialNumber)
    {
        $device = IpgDevice::where('ipg_serial_number', $serialNumber)->first();
        
        if (!$device) {
            return response()->json([
                'message' => 'IPG device not found'
            ], 404);
        }
        
        return response()->json([
            'device' => [
                'ipg_serial_number' => $device->ipg_serial_number,
                'ipg_model_name' => $device->ipg_model_name,
                'ipg_model_number' => $device->ipg_model_number,
                'is_linked' => $device->is_linked
            ]
        ]);
    }

   


}